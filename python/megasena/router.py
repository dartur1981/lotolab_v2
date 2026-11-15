import os
import sys
import datetime
import math
import json
import unicodedata
import pandas as pd
from fastapi import APIRouter, Depends, HTTPException, BackgroundTasks
from pydantic import BaseModel
from sqlalchemy.orm import Session
from sqlalchemy.dialects.mysql import insert

from megasena.database import engine, engine_app, get_db, Base, init_databases
from megasena import models
from megasena.calculadora_ciclos import calcular_ciclos_megasena, Concurso as ConcursoCalc
from megasena.analisador_tendencias import analisar_tendencias
from megasena.gerador_fechamentos import gerar_jogos_fechamento_megasena, PRIMOS, FIBONACCI, calc_linha, calc_coluna, calc_quadrante, calc_sub_quadrante
from megasena.treinador_ml import treinar_modelo_rf
from megasena.preditor_ml import prever_proximo_sorteio
from megasena.classificador_temperatura import (
    calcular_temperaturas_historicas_sem_lookahead,
    obter_estatisticas_historicas_temperatura,
    calcular_temperatura_atual
)

def garantir_colunas_temperatura(db_engine):
    from sqlalchemy import text
    with db_engine.connect() as conn:
        for col in ["temp_quente", "temp_morno", "temp_frio"]:
            try:
                conn.execute(text(f"ALTER TABLE features_megasena ADD COLUMN `{col}` INT NOT NULL DEFAULT 0;"))
                conn.commit()
            except Exception:
                pass
        colunas_tendencias = [
            ("score_temperatura", "FLOAT NOT NULL DEFAULT 0.0"),
            ("r_10", "FLOAT NOT NULL DEFAULT 0.0"),
            ("r_20", "FLOAT NOT NULL DEFAULT 0.0"),
            ("r_50", "FLOAT NOT NULL DEFAULT 0.0"),
            ("r_100", "FLOAT NOT NULL DEFAULT 0.0"),
        ]
        for col, col_type in colunas_tendencias:
            try:
                conn.execute(text(f"ALTER TABLE tendencias_dezenas_megasena ADD COLUMN `{col}` {col_type};"))
                conn.commit()
            except Exception:
                pass

try:
    init_databases()
    Base.metadata.create_all(bind=engine)
    garantir_colunas_temperatura(engine)
    print("Tabelas da Mega-Sena verificadas/criadas com sucesso!")
except Exception as e:
    print(f"Aviso: Banco de dados da Mega-Sena ainda não disponível na inicialização ({e}).")

router = APIRouter(prefix="/megasena", tags=["megasena"])

class ImportRequest(BaseModel):
    path: str

class GerarFechamentoRequest(BaseModel):
    bolao_id: int
    fechamento_id: int
    quantidade_jogos: int
    dezenas: list[int]

def log_msg(log_filepath: str, msg: str):
    timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    formatted = f"[{timestamp}] {msg}\n"
    with open(log_filepath, "a", encoding="utf-8") as f:
        f.write(formatted)
    print(formatted.strip())

def resolve_data_path(file_path: str) -> str:
    def strip_accents(s: str) -> str:
        return ''.join(c for c in unicodedata.normalize('NFD', s) if unicodedata.category(c) != 'Mn').lower()

    if os.path.exists(file_path):
        return file_path

    norm = os.path.normpath(file_path).replace("\\", "/")
    if os.path.exists(norm):
        return norm

    if norm.startswith("/var/www/data"):
        alt = norm.replace("/var/www/data", "/data", 1)
        if os.path.exists(alt):
            return alt
    elif norm.startswith("/data"):
        alt = norm.replace("/data", "/var/www/data", 1)
        if os.path.exists(alt):
            return alt

    clean_name = os.path.basename(file_path)
    clean_no_accents = strip_accents(clean_name)
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

    candidates = [
        os.path.join("/data", clean_name),
        os.path.join("/data/historicos", clean_name),
        os.path.join("/var/www/data", clean_name),
        os.path.join("/var/www/data/historicos", clean_name),
        os.path.join(base_dir, "data", clean_name),
        os.path.join(base_dir, "data", "historicos", clean_name),
    ]
    for candidate in candidates:
        if os.path.exists(candidate):
            return candidate

    search_dirs = [
        "/data/historicos",
        "/data",
        "/var/www/data/historicos",
        "/var/www/data",
        os.path.join(base_dir, "data", "historicos"),
        os.path.join(base_dir, "data"),
    ]
    for sdir in search_dirs:
        if os.path.isdir(sdir):
            for entry in os.listdir(sdir):
                if strip_accents(entry) == clean_no_accents:
                    candidate = os.path.join(sdir, entry)
                    if os.path.exists(candidate):
                        return candidate

    for sdir in ["/data/historicos", "/var/www/data/historicos", os.path.join(base_dir, "data", "historicos")]:
        if os.path.isdir(sdir):
            files = [
                os.path.join(sdir, f) for f in os.listdir(sdir)
                if f.lower().endswith(('.xlsx', '.xls', '.csv')) and not f.startswith('~$')
            ]
            if files:
                files.sort(key=lambda x: os.path.getmtime(x), reverse=True)
                return files[0]

    return norm if os.path.exists(norm) else file_path

def processar_importacao_megasena_background(req_path: str, log_filepath: str):
    from megasena.database import SessionLocal
    db = SessionLocal()
    
    def add_log(msg):
        log_msg(log_filepath, msg)
        
    try:
        # Garante a existência das novas colunas na base analítica antes de qualquer operação
        try:
            garantir_colunas_temperatura(db.bind)
        except Exception as ge:
            add_log(f"Aviso ao verificar colunas no banco analítico: {str(ge)}")

        resolved_path = resolve_data_path(req_path)
        add_log(f"Iniciando importação da Mega-Sena em background: {resolved_path}")

        if not os.path.exists(resolved_path):
            add_log(f"Erro: Arquivo não encontrado (recebido: {req_path}, resolvido: {resolved_path}).")
            return

        try:
            add_log("Lendo arquivo com Pandas...")
            if resolved_path.endswith('.csv'):
                df = pd.read_csv(resolved_path)
            else:
                df = pd.read_excel(resolved_path)
        except Exception as e:
            add_log(f"Erro ao ler arquivo: {str(e)}")
            return

        # Normaliza nomes de colunas (case insensitive e sem espaços extras)
        col_map = {c.strip().lower(): c for c in df.columns}
        
        # Identifica coluna de Concurso
        concurso_col = None
        for cand in ['concurso', 'concurso_numero', 'num_concurso']:
            if cand in col_map:
                concurso_col = col_map[cand]
                break
                
        # Identifica coluna de Data
        data_col = None
        for cand in ['data do sorteio', 'data sorteio', 'datasorteio', 'data']:
            if cand in col_map:
                data_col = col_map[cand]
                break
                
        # Identifica colunas de Bolas 1 a 6
        bolas_cols = []
        for i in range(1, 7):
            c_found = None
            for pattern in [f'bola{i}', f'bola {i}', f'dezena {i}', f'dezena{i}', f'{i}ª dezena', f'{i}a dezena']:
                if pattern in col_map:
                    c_found = col_map[pattern]
                    break
            if c_found:
                bolas_cols.append(c_found)

        if not concurso_col or not data_col or len(bolas_cols) < 6:
            add_log(f"Erro: Não foi possível mapear todas as colunas necessárias (Concurso: {concurso_col}, Data: {data_col}, Bolas encontradas: {len(bolas_cols)}/6).")
            return

        df[data_col] = pd.to_datetime(df[data_col], format='%d/%m/%Y', errors='coerce')
        
        resultados_data = []
        features_data = []

        add_log(f"Iterando e calculando features de {len(df)} sorteios da Mega-Sena...")
        for _, row in df.iterrows():
            concurso = row[concurso_col]
            if pd.isna(concurso) or math.isnan(concurso):
                continue
                
            concurso = int(concurso)
            bolas = [int(row[col]) for col in bolas_cols]
            
            res_dict = {
                "concurso": concurso,
                "data_sorteio": row[data_col].date() if pd.notna(row[data_col]) else datetime.date.today(),
                "bola_1": bolas[0],
                "bola_2": bolas[1],
                "bola_3": bolas[2],
                "bola_4": bolas[3],
                "bola_5": bolas[4],
                "bola_6": bolas[5],
            }
            resultados_data.append(res_dict)

            # Features
            pares = sum(1 for b in bolas if b % 2 == 0)
            impares = 6 - pares
            primos = sum(1 for b in bolas if b in PRIMOS)
            fibonacci = sum(1 for b in bolas if b in FIBONACCI)
            soma = sum(bolas)
            
            q1 = sum(1 for b in bolas if calc_quadrante(b) == 1)
            q2 = sum(1 for b in bolas if calc_quadrante(b) == 2)
            q3 = sum(1 for b in bolas if calc_quadrante(b) == 3)

            feat_dict = {
                "concurso": concurso,
                "pares": pares,
                "impares": impares,
                "primos": primos,
                "fibonacci": fibonacci,
                "soma": soma,
                "quadrante_1": q1,
                "quadrante_2": q2,
                "quadrante_3": q3,
            }
            for s in range(1, 16):
                feat_dict[f"sub_{s}"] = sum(1 for b in bolas if calc_sub_quadrante(b) == s)
            for l in range(1, 7):
                feat_dict[f"linha_{l}"] = sum(1 for b in bolas if calc_linha(b) == l)
            for c in range(1, 11):
                feat_dict[f"coluna_{c}"] = sum(1 for b in bolas if calc_coluna(b) == c)

            features_data.append(feat_dict)

        add_log("Inserindo resultados no banco de dados analítico...")
        for item in resultados_data:
            stmt = insert(models.ResultadoMegasena).values(item)
            stmt = stmt.on_duplicate_key_update(
                data_sorteio=stmt.inserted.data_sorteio,
                bola_1=stmt.inserted.bola_1,
                bola_2=stmt.inserted.bola_2,
                bola_3=stmt.inserted.bola_3,
                bola_4=stmt.inserted.bola_4,
                bola_5=stmt.inserted.bola_5,
                bola_6=stmt.inserted.bola_6
            )
            db.execute(stmt)

        add_log("Calculando temperaturas históricas das dezenas (sem lookahead bias)...")
        try:
            df_res_temp = pd.DataFrame(resultados_data).sort_values("concurso", ascending=True)
            df_feat_temp, _ = calcular_temperaturas_historicas_sem_lookahead(df_res_temp)
            temp_map = df_feat_temp.set_index("concurso").to_dict(orient="index")
            for f_item in features_data:
                c = f_item["concurso"]
                t_vals = temp_map.get(c, {"temp_quente": 0, "temp_morno": 0, "temp_frio": 0})
                f_item["temp_quente"] = int(t_vals["temp_quente"])
                f_item["temp_morno"] = int(t_vals["temp_morno"])
                f_item["temp_frio"] = int(t_vals["temp_frio"])
        except Exception as te:
            add_log(f"Aviso ao calcular temperaturas históricas: {str(te)}")

        add_log("Inserindo features estatísticas no banco de dados analítico...")
        for item in features_data:
            stmt = insert(models.FeaturesMegasena).values(item)
            update_cols = {c: getattr(stmt.inserted, c) for c in item.keys() if c != "concurso"}
            stmt = stmt.on_duplicate_key_update(**update_cols)
            db.execute(stmt)

        db.commit()

        add_log("Calculando ciclos da Mega-Sena...")
        todos_sorteios = db.query(models.ResultadoMegasena).order_by(models.ResultadoMegasena.concurso.asc()).all()
        concursos_calc = []
        for r in todos_sorteios:
            dezenas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5, r.bola_6]
            concursos_calc.append(ConcursoCalc(numero=r.concurso, dezenas=dezenas))
            
        relatorio = calcular_ciclos_megasena(concursos_calc)
        
        # Limpa ciclos antigos e persiste os calculados
        db.query(models.CicloMegasena).delete()
        for cf in relatorio.ciclos_fechados:
            c_db = models.CicloMegasena(
                numero_ciclo=cf.numero_ciclo,
                concurso_inicial=cf.concurso_inicial,
                concurso_final=cf.concurso_final,
                duracao=cf.duracao,
                status="FECHADO",
                json_matriz=json.dumps(cf.json_matriz)
            )
            db.add(c_db)
            
        ca = relatorio.ciclo_atual
        ca_db = models.CicloMegasena(
            numero_ciclo=ca.numero_ciclo,
            concurso_inicial=ca.concurso_inicial,
            concurso_final=None,
            duracao=ca.concursos_transcorridos,
            status="ABERTO",
            json_matriz=json.dumps(ca.json_matriz)
        )
        db.add(ca_db)
        db.commit()

        add_log("Executando análise de tendências da Mega-Sena...")
        analisar_tendencias(log_filepath, db)

        add_log("Processo em background finalizado com SUCESSO para a Mega-Sena!")
        
    except Exception as e:
        add_log(f"ERRO no processo de background da Mega-Sena: {str(e)}")
        db.rollback()
    finally:
        db.close()

@router.post("/importar-historico")
def endpoint_importar_historico(req: ImportRequest, background_tasks: BackgroundTasks):
    logs_dir = os.getenv("LOGS_DIR", "/logs" if os.path.exists("/logs") else "/var/www/logs")
    if not os.path.exists(logs_dir):
        base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
        logs_dir = os.path.join(base_dir, "logs")
    os.makedirs(logs_dir, exist_ok=True)
    
    log_filename = f"importacao_megasena_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
    log_filepath = os.path.join(logs_dir, log_filename)
    
    with open(log_filepath, "w", encoding="utf-8") as f:
        f.write("")
        
    log_msg(log_filepath, "Requisição recebida para a Mega-Sena. Disparando background...")
    background_tasks.add_task(processar_importacao_megasena_background, req.path, log_filepath)

    return {
        "status": "ok", 
        "message": "Processamento da Mega-Sena disparado com sucesso.",
        "log_file": log_filename
    }

@router.post("/gerar-fechamento")
def endpoint_gerar_fechamento(req: GerarFechamentoRequest, db: Session = Depends(get_db)):
    try:
        resultado = gerar_jogos_fechamento_megasena(
            db=db,
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

@router.get("/ciclos")
def endpoint_get_ciclos(db: Session = Depends(get_db)):
    ciclo_aberto = db.query(models.CicloMegasena).filter_by(status="ABERTO").first()
    if not ciclo_aberto:
        return {"status": "empty", "message": "Nenhum ciclo cadastrado."}
    return {
        "status": "ok",
        "ciclo": ciclo_aberto.numero_ciclo,
        "inicio": ciclo_aberto.concurso_inicial,
        "concursos_decorridos": ciclo_aberto.duracao,
        "dados": json.loads(ciclo_aberto.json_matriz) if ciclo_aberto.json_matriz else {}
    }

@router.get("/tendencias")
def endpoint_get_tendencias(db: Session = Depends(get_db)):
    tendencias = db.query(models.TendenciaDezenaMegasena).order_by(models.TendenciaDezenaMegasena.score.desc()).all()
    return {
        "status": "ok",
        "tendencias": [
            {
                "dezena": t.dezena,
                "frequencia": t.frequencia,
                "atraso": t.atraso,
                "score_temperatura": getattr(t, "score_temperatura", 0.0),
                "r_10": getattr(t, "r_10", 0.0),
                "r_20": getattr(t, "r_20", 0.0),
                "r_50": getattr(t, "r_50", 0.0),
                "r_100": getattr(t, "r_100", 0.0),
                "temperatura": t.temperatura,
                "status_ciclo": t.status_ciclo,
                "recomendacao": t.recomendacao,
                "score": t.score
            }
            for t in tendencias
        ]
    }

@router.get("/temperaturas/estatisticas")
def endpoint_get_estatisticas_temperatura(db: Session = Depends(get_db)):
    """Retorna as médias históricas de temp_quente, temp_morno, temp_frio e os perfis mais comuns."""
    features = db.query(
        models.FeaturesMegasena.concurso,
        models.FeaturesMegasena.temp_quente,
        models.FeaturesMegasena.temp_morno,
        models.FeaturesMegasena.temp_frio
    ).order_by(models.FeaturesMegasena.concurso.asc()).all()

    if not features:
        return {"status": "empty", "message": "Nenhum histórico de features cadastrado."}

    df_feat = pd.DataFrame([
        {
            "concurso": f.concurso,
            "temp_quente": f.temp_quente,
            "temp_morno": f.temp_morno,
            "temp_frio": f.temp_frio
        }
        for f in features
    ])

    estatisticas = obter_estatisticas_historicas_temperatura(df_feat)
    return {"status": "ok", "dados": estatisticas}

@router.post("/reprocessar-temperaturas-historicas")
def endpoint_reprocessar_temperaturas_historicas(db: Session = Depends(get_db)):
    """
    Reprocessa toda a base histórica existente de sorteios:
    1. Calcula temp_quente, temp_morno, temp_frio retroativamente sem lookahead bias.
    2. Atualiza a tabela features_megasena.
    3. Executa a análise de temperatura atual e atualiza tendencias_dezenas_megasena.
    """
    try:
        sorteios = db.query(models.ResultadoMegasena).filter(
            models.ResultadoMegasena.concurso != 9999
        ).order_by(models.ResultadoMegasena.concurso.asc()).all()

        if not sorteios:
            return {"status": "empty", "message": "Nenhum sorteio encontrado para processar."}

        df_sorteios = pd.DataFrame([
            {
                "concurso": s.concurso,
                "bola_1": s.bola_1,
                "bola_2": s.bola_2,
                "bola_3": s.bola_3,
                "bola_4": s.bola_4,
                "bola_5": s.bola_5,
                "bola_6": s.bola_6
            }
            for s in sorteios
        ])

        df_feat_temp, _ = calcular_temperaturas_historicas_sem_lookahead(df_sorteios)
        temp_dict = df_feat_temp.set_index("concurso").to_dict(orient="index")

        # Atualiza features_megasena em lote
        from sqlalchemy import update
        for conc, vals in temp_dict.items():
            db.execute(
                update(models.FeaturesMegasena)
                .where(models.FeaturesMegasena.concurso == conc)
                .values(
                    temp_quente=int(vals["temp_quente"]),
                    temp_morno=int(vals["temp_morno"]),
                    temp_frio=int(vals["temp_frio"])
                )
            )

        db.commit()

        # Recalcula tendências atuais
        logs_dir = os.getenv("LOGS_DIR", "logs")
        os.makedirs(logs_dir, exist_ok=True)
        log_file = os.path.join(logs_dir, "reprocessamento_temperaturas.log")
        analisar_tendencias(log_file, db)

        estatisticas = obter_estatisticas_historicas_temperatura(df_feat_temp)
        return {
            "status": "success",
            "message": f"Temperaturas históricas de {len(sorteios)} concursos recalculadas com sucesso!",
            "estatisticas": estatisticas
        }
    except Exception as e:
        db.rollback()
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/treinar-modelo")
def api_treinar_modelo(db: Session = Depends(get_db)):
    try:
        resultado = treinar_modelo_rf(db)
        if resultado["status"] == "error":
            raise HTTPException(status_code=400, detail=resultado["message"])
        return resultado
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/prever")
def api_prever(db: Session = Depends(get_db)):
    try:
        resultado = prever_proximo_sorteio(db)
        if resultado["status"] == "error":
            raise HTTPException(status_code=400, detail=resultado["message"])
        return resultado
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/setup-tables")
@router.post("/setup-tables")
def setup_tables():
    try:
        init_databases()
        Base.metadata.create_all(bind=engine)
        garantir_colunas_temperatura(engine)
        from sqlalchemy import inspect
        tables = inspect(engine).get_table_names()
        return {"status": "success", "message": "Tabelas e colunas de temperatura da Mega-Sena verificadas/criadas com sucesso", "tables": tables}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
