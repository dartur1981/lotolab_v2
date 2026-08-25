import os
import datetime
import pandas as pd
from fastapi import FastAPI, Depends, HTTPException, BackgroundTasks
from pydantic import BaseModel
from sqlalchemy.orm import Session
from sqlalchemy.dialects.mysql import insert
import math
import json

from database import engine, engine_app, get_db, Base
import models
from calculadora_ciclos import calcular_ciclos_lotofacil, Concurso as ConcursoCalc
from calculadora_combinacoes import processar_combinacoes_background, log_msg
from analisador_tendencias import analisar_tendencias
from gerador_fechamentos import gerar_jogos_fechamento
from analisador_estrategias import sincronizar_estatisticas_avancadas

Base.metadata.create_all(bind=engine)

app = FastAPI(title="Lotolab Python API", version="1.0.0")

class ImportRequest(BaseModel):
    path: str

class GerarFechamentoRequest(BaseModel):
    bolao_id: int
    fechamento_id: int
    quantidade_jogos: int
    dezenas: list[int]

PRIMOS = {2, 3, 5, 7, 11, 13, 17, 19, 23}
FIBONACCI = {1, 2, 3, 5, 8, 13, 21}
MOLDURA = {1, 2, 3, 4, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, 25}
MIOLO = {7, 8, 9, 12, 13, 14, 17, 18, 19}

def calc_linha(n):
    return (n - 1) // 5 + 1

def calc_coluna(n):
    return (n - 1) % 5 + 1

def processar_importacao_completa_background(req_path: str, log_filepath: str):
    from database import SessionLocal
    db = SessionLocal()
    
    def add_log(msg):
        log_msg(log_filepath, msg)
        
    try:
        add_log(f"Iniciando importação do arquivo em background: {req_path}")

        if not os.path.exists(req_path):
            add_log("Erro: Arquivo não encontrado.")
            return

        try:
            add_log("Lendo arquivo com Pandas...")
            if req_path.endswith('.csv'):
                df = pd.read_csv(req_path)
            else:
                df = pd.read_excel(req_path)
        except Exception as e:
            add_log(f"Erro ao ler arquivo: {str(e)}")
            return

        add_log("Validando colunas do arquivo...")
        required_cols = ['Concurso', 'Data Sorteio'] + [f'Bola{i}' for i in range(1, 16)]
        for c in required_cols:
            if c not in df.columns:
                add_log(f"Erro: Coluna {c} faltando no arquivo.")
                return

        df['Data Sorteio'] = pd.to_datetime(df['Data Sorteio'], format='%d/%m/%Y', errors='coerce')
        
        resultados_data = []
        features_data = []

        add_log(f"Iterando e calculando features de {len(df)} registros...")
        for _, row in df.iterrows():
            concurso = row['Concurso']
            if pd.isna(concurso) or math.isnan(concurso):
                continue
                
            concurso = int(concurso)
            
            # Read the 15 balls
            bolas = []
            for i in range(1, 16):
                val = row[f'Bola{i}']
                bolas.append(int(val))

            # Build Resultado dict
            res_dict = {
                'concurso': concurso,
                'data_sorteio': row['Data Sorteio'].date() if not pd.isna(row['Data Sorteio']) else None,
            }
            for i, b in enumerate(bolas, 1):
                res_dict[f'bola_{i}'] = b
                
            resultados_data.append(res_dict)

            # Calculate Features
            pares = sum(1 for b in bolas if b % 2 == 0)
            impares = 15 - pares
            primos = sum(1 for b in bolas if b in PRIMOS)
            fibonacci = sum(1 for b in bolas if b in FIBONACCI)
            moldura = sum(1 for b in bolas if b in MOLDURA)
            miolo = sum(1 for b in bolas if b in MIOLO)
            soma = sum(bolas)
            
            linhas_count = {1:0, 2:0, 3:0, 4:0, 5:0}
            colunas_count = {1:0, 2:0, 3:0, 4:0, 5:0}
            
            for b in bolas:
                linhas_count[calc_linha(b)] += 1
                colunas_count[calc_coluna(b)] += 1
                
            feat_dict = {
                'concurso': concurso,
                'pares': pares,
                'impares': impares,
                'primos': primos,
                'fibonacci': fibonacci,
                'moldura': moldura,
                'miolo': miolo,
                'soma': soma,
                'linha_1': linhas_count[1],
                'linha_2': linhas_count[2],
                'linha_3': linhas_count[3],
                'linha_4': linhas_count[4],
                'linha_5': linhas_count[5],
                'coluna_1': colunas_count[1],
                'coluna_2': colunas_count[2],
                'coluna_3': colunas_count[3],
                'coluna_4': colunas_count[4],
                'coluna_5': colunas_count[5],
            }
            features_data.append(feat_dict)

        if resultados_data:
            add_log("Salvando dados extraídos no banco de dados analítico...")
            # Bulk upsert resultados
            stmt_res = insert(models.ResultadoLotofacil).values(resultados_data)
            update_dict_res = {c.name: c for c in stmt_res.inserted if c.name != 'concurso'}
            if update_dict_res:
                stmt_res = stmt_res.on_duplicate_key_update(**update_dict_res)
            else:
                stmt_res = stmt_res.prefix_with('IGNORE')
            db.execute(stmt_res)
            
            # Bulk upsert features
            stmt_feat = insert(models.FeaturesLotofacil).values(features_data)
            update_dict_feat = {c.name: c for c in stmt_feat.inserted if c.name != 'concurso'}
            if update_dict_feat:
                stmt_feat = stmt_feat.on_duplicate_key_update(**update_dict_feat)
            else:
                stmt_feat = stmt_feat.prefix_with('IGNORE')
            db.execute(stmt_feat)

            db.commit()
            add_log("Transação finalizada com sucesso.")

        add_log("Iniciando cálculo de ciclos da Lotofácil com todo o histórico...")
        
        # Busca todo o histórico do banco para calcular os ciclos corretamente
        todos_resultados = db.query(models.ResultadoLotofacil).order_by(models.ResultadoLotofacil.concurso.asc()).all()
        
        concursos_calc = []
        for r in todos_resultados:
            dezenas = [
                r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5,
                r.bola_6, r.bola_7, r.bola_8, r.bola_9, r.bola_10,
                r.bola_11, r.bola_12, r.bola_13, r.bola_14, r.bola_15
            ]
            concursos_calc.append(ConcursoCalc(numero=r.concurso, dezenas=dezenas))
            
        relatorio_ciclos = calcular_ciclos_lotofacil(concursos_calc)
        
        # Prepara os dados para salvar no banco
        ciclos_db_data = []
        
        for c in relatorio_ciclos.ciclos_fechados:
            ciclos_db_data.append({
                "numero_ciclo": c.numero_ciclo,
                "concurso_inicial": c.concurso_inicial,
                "concurso_final": c.concurso_final,
                "duracao": c.duracao,
                "status": "FECHADO",
                "json_matriz": json.dumps(c.json_matriz)
            })
            
        ciclos_db_data.append({
            "numero_ciclo": relatorio_ciclos.ciclo_atual.numero_ciclo,
            "concurso_inicial": relatorio_ciclos.ciclo_atual.concurso_inicial,
            "concurso_final": None,
            "duracao": relatorio_ciclos.ciclo_atual.concursos_transcorridos,
            "status": "ABERTO",
            "json_matriz": json.dumps(relatorio_ciclos.ciclo_atual.json_matriz)
        })
        
        # Salva os ciclos no banco usando upsert
        stmt_ciclos = insert(models.CicloLotofacil).values(ciclos_db_data)
        update_dict_ciclos = {c.name: c for c in stmt_ciclos.inserted if c.name != 'numero_ciclo'}
        if update_dict_ciclos:
            stmt_ciclos = stmt_ciclos.on_duplicate_key_update(**update_dict_ciclos)
        else:
            stmt_ciclos = stmt_ciclos.prefix_with('IGNORE')
        db.execute(stmt_ciclos)
        db.commit()
        
        add_log(f"Cálculo de ciclos concluído. {len(relatorio_ciclos.ciclos_fechados)} ciclos fechados registrados e 1 ciclo aberto (Ciclo {relatorio_ciclos.ciclo_atual.numero_ciclo}).")
        add_log(f"Importação inicial concluída. {len(resultados_data)} resultados extraídos.")

        # Disparar calculo de tendencias
        add_log("Iniciando cálculo de tendências das dezenas (Quentes, Frias e Ciclos)...")
        analisar_tendencias(log_filepath, db)

        # Disparar calculo de combinacoes
        draws_history = []
        for r in todos_resultados:
            d = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5,
                 r.bola_6, r.bola_7, r.bola_8, r.bola_9, r.bola_10,
                 r.bola_11, r.bola_12, r.bola_13, r.bola_14, r.bola_15]
            draws_history.append(d)
            
        processar_combinacoes_background(log_filepath, engine, draws_history)

    except Exception as e:
        add_log(f"Erro fatal durante o processamento em background: {str(e)}")
    finally:
        db.close()


@app.post("/importar-historico")
def importar_historico(req: ImportRequest, background_tasks: BackgroundTasks):
    logs_dir = "d:/SISTEMAS-PRIVATE/lotolab_app_v2/logs"
    os.makedirs(logs_dir, exist_ok=True)
    
    # Limpa logs antigos
    for filename in os.listdir(logs_dir):
        file_path = os.path.join(logs_dir, filename)
        try:
            if os.path.isfile(file_path):
                os.remove(file_path)
        except Exception:
            pass

    log_filename = f"importacao_lotofacil_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
    log_filepath = os.path.join(logs_dir, log_filename)
    
    # Garante arquivo vazio
    with open(log_filepath, "w", encoding="utf-8") as f:
        f.write("")
        
    log_msg(log_filepath, "Requisição recebida. Encaminhando todo o processo para background...")

    # Envia TUDO pro background
    background_tasks.add_task(processar_importacao_completa_background, req.path, log_filepath)

    return {
        "status": "ok", 
        "message": "Processamento assíncrono disparado com sucesso.",
        "log_file": log_filename
    }

@app.post("/gerar-fechamento")
def endpoint_gerar_fechamento(req: GerarFechamentoRequest, db: Session = Depends(get_db)):
    try:
        resultado = gerar_jogos_fechamento(
            db=db,
            engine=engine,
            engine_app=engine_app,
            bolao_id=req.bolao_id,
            fechamento_id=req.fechamento_id,
            quantidade_jogos=req.quantidade_jogos,
            dezenas_pool=req.dezenas
        )
        if resultado.get("status") == "error":
            raise HTTPException(status_code=400, detail=resultado["message"])
            
        return resultado
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/atualizar-estrategias")
def endpoint_atualizar_estrategias(db: Session = Depends(get_db)):
    try:
        return sincronizar_estatisticas_avancadas(db, engine)
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

from treinador_ml import treinar_modelo_rf
from preditor_ml import prever_proximo_sorteio

@app.post("/treinar-modelo")
def api_treinar_modelo(db: Session = Depends(get_db)):
    try:
        resultado = treinar_modelo_rf(db)
        if resultado["status"] == "error":
            raise HTTPException(status_code=400, detail=resultado["message"])
        return resultado
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/prever-sorteio")
def api_prever_sorteio(db: Session = Depends(get_db)):
    try:
        resultado = prever_proximo_sorteio(db)
        if resultado["status"] == "error":
            raise HTTPException(status_code=400, detail=resultado["message"])
        return resultado
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="127.0.0.1", port=5000, reload=True)
