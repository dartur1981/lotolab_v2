import os
import sys
import datetime
import math
import json
import unicodedata
import pandas as pd
from typing import List
from fastapi import APIRouter, HTTPException, Depends, BackgroundTasks
from pydantic import BaseModel
from sqlalchemy.orm import Session
from sqlalchemy.dialects.mysql import insert

from quina.database import engine, engine_app, get_db, Base, init_databases
from quina import models
from quina.analisador_tendencias import analisar_tendencias
from quina.gerador_fechamentos import (
    gerar_jogos_fechamento_quina,
    PRIMOS_QUINA,
    FIBONACCI_QUINA,
    calc_linha,
    calc_coluna,
    calc_quadrante_quina
)

def garantir_colunas_temperatura_quina(db_engine):
    from sqlalchemy import text
    with db_engine.connect() as conn:
        cols_features = ["temp_quente", "temp_morno", "temp_frio"]
        for col in cols_features:
            try:
                conn.execute(text(f"ALTER TABLE features_quina ADD COLUMN `{col}` INT NOT NULL DEFAULT 0;"))
                conn.commit()
            except Exception:
                pass

# Inicializa banco e tabelas da Quina
try:
    init_databases()
    Base.metadata.create_all(bind=engine)
    garantir_colunas_temperatura_quina(engine)
    print("Tabelas da Quina verificadas/criadas com sucesso!")
except Exception as e:
    print(f"Aviso: Banco de dados da Quina ainda não disponível na inicialização ({e}).")

router = APIRouter(prefix="/quina", tags=["quina"])

class ImportRequest(BaseModel):
    path: str

class GerarFechamentoQuinaRequest(BaseModel):
    bolao_id: int
    fechamento_id: int
    quantidade_jogos: int
    dezenas: List[int]

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
        os.path.join(base_dir, "data", "historicos", clean_name),
        os.path.join(base_dir, "data", clean_name),
        os.path.join("/data", "historicos", clean_name),
        os.path.join("/var/www/data", "historicos", clean_name),
        os.path.join("data", "historicos", clean_name),
    ]

    for cand in candidates:
        if os.path.exists(cand):
            return cand

    search_dirs = [
        os.path.join(base_dir, "data", "historicos"),
        os.path.join(base_dir, "data"),
        "/data/historicos",
        "/var/www/data/historicos"
    ]

    for sdir in search_dirs:
        if os.path.isdir(sdir):
            for fname in os.listdir(sdir):
                if strip_accents(fname) == clean_no_accents:
                    return os.path.join(sdir, fname)

    for sdir in search_dirs:
        if os.path.isdir(sdir):
            files = [
                os.path.join(sdir, f) for f in os.listdir(sdir)
                if f.lower().endswith(('.xlsx', '.xls', '.csv')) and not f.startswith('~$')
            ]
            if files:
                files.sort(key=lambda x: os.path.getmtime(x), reverse=True)
                return files[0]

    return norm if os.path.exists(norm) else file_path

def processar_importacao_quina_background(req_path: str, log_filepath: str):
    from quina.database import SessionLocal
    db = SessionLocal()

    def add_log(msg):
        log_msg(log_filepath, msg)

    try:
        resolved_path = resolve_data_path(req_path)
        add_log(f"Iniciando importação da Quina em background: {resolved_path}")

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

        # Normaliza nomes de colunas
        col_map = {c.strip().lower(): c for c in df.columns}

        # Concurso
        concurso_col = None
        for cand in ['concurso', 'concurso_numero', 'num_concurso', 'concurso numero']:
            if cand in col_map:
                concurso_col = col_map[cand]
                break

        # Data
        data_col = None
        for cand in ['data do sorteio', 'data sorteio', 'datasorteio', 'data']:
            if cand in col_map:
                data_col = col_map[cand]
                break

        # Bolas 1 a 5
        bolas_cols = []
        for i in range(1, 6):
            c_found = None
            for pattern in [f'bola{i}', f'bola {i}', f'dezena {i}', f'dezena{i}', f'{i}ª dezena', f'{i}a dezena']:
                if pattern in col_map:
                    c_found = col_map[pattern]
                    break
            if c_found:
                bolas_cols.append(c_found)

        if not concurso_col or not data_col or len(bolas_cols) < 5:
            add_log(f"Erro: Não foi possível mapear todas as colunas necessárias (Concurso: {concurso_col}, Data: {data_col}, Bolas encontradas: {len(bolas_cols)}/5).")
            return

        df[data_col] = pd.to_datetime(df[data_col], format='%d/%m/%Y', errors='coerce')

        resultados_data = []
        features_data = []

        add_log(f"Iterando e calculando features de {len(df)} sorteios da Quina...")
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
            }
            resultados_data.append(res_dict)

            # Features
            pares = sum(1 for b in bolas if b % 2 == 0)
            impares = 5 - pares
            primos = sum(1 for b in bolas if b in PRIMOS_QUINA)
            fibonacci = sum(1 for b in bolas if b in FIBONACCI_QUINA)
            soma = sum(bolas)

            q1 = sum(1 for b in bolas if calc_quadrante_quina(b) == 1)
            q2 = sum(1 for b in bolas if calc_quadrante_quina(b) == 2)
            q3 = sum(1 for b in bolas if calc_quadrante_quina(b) == 3)
            q4 = sum(1 for b in bolas if calc_quadrante_quina(b) == 4)

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
                "quadrante_4": q4,
            }
            for l in range(1, 9):
                feat_dict[f"linha_{l}"] = sum(1 for b in bolas if calc_linha(b) == l)
            for c in range(1, 11):
                feat_dict[f"coluna_{c}"] = sum(1 for b in bolas if calc_coluna(b) == c)

            features_data.append(feat_dict)

        add_log("Inserindo resultados no banco de dados analítico da Quina...")
        if resultados_data:
            stmt_res = insert(models.ResultadoQuina).values(resultados_data)
            stmt_res = stmt_res.on_duplicate_key_update(
                data_sorteio=stmt_res.inserted.data_sorteio,
                bola_1=stmt_res.inserted.bola_1,
                bola_2=stmt_res.inserted.bola_2,
                bola_3=stmt_res.inserted.bola_3,
                bola_4=stmt_res.inserted.bola_4,
                bola_5=stmt_res.inserted.bola_5,
            )
            db.execute(stmt_res)
            db.commit()

            stmt_feat = insert(models.FeaturesQuina).values(features_data)
            update_dict = {
                "pares": stmt_feat.inserted.pares,
                "impares": stmt_feat.inserted.impares,
                "primos": stmt_feat.inserted.primos,
                "fibonacci": stmt_feat.inserted.fibonacci,
                "soma": stmt_feat.inserted.soma,
                "quadrante_1": stmt_feat.inserted.quadrante_1,
                "quadrante_2": stmt_feat.inserted.quadrante_2,
                "quadrante_3": stmt_feat.inserted.quadrante_3,
                "quadrante_4": stmt_feat.inserted.quadrante_4,
            }
            for l in range(1, 9):
                update_dict[f"linha_{l}"] = getattr(stmt_feat.inserted, f"linha_{l}")
            for c in range(1, 11):
                update_dict[f"coluna_{c}"] = getattr(stmt_feat.inserted, f"coluna_{c}")

            stmt_feat = stmt_feat.on_duplicate_key_update(**update_dict)
            db.execute(stmt_feat)
            db.commit()

        add_log(f"Sucesso! {len(resultados_data)} concursos da Quina processados e gravados.")

        # Executa análise de temperatura e tendências
        add_log("Calculando temperatura das 80 dezenas da Quina...")
        analisar_tendencias(log_filepath, db)

        add_log("Processo em background finalizado com SUCESSO!")

    except Exception as e:
        add_log(f"ERRO no processo de background da Quina: {str(e)}")
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

    log_filename = f"importacao_quina_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
    log_filepath = os.path.join(logs_dir, log_filename)

    with open(log_filepath, "w", encoding="utf-8") as f:
        f.write("")

    log_msg(log_filepath, "Requisição recebida para a Quina. Disparando background...")
    background_tasks.add_task(processar_importacao_quina_background, req.path, log_filepath)

    return {
        "status": "ok",
        "message": "Processamento da Quina disparado com sucesso.",
        "log_file": log_filename
    }

@router.post("/recalcular-temperaturas")
def endpoint_recalcular_temperaturas(db: Session = Depends(get_db)):
    try:
        logs_dir = os.getenv("LOGS_DIR", "/logs" if os.path.exists("/logs") else "/var/www/logs")
        if not os.path.exists(logs_dir):
            base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
            logs_dir = os.path.join(base_dir, "logs")
        os.makedirs(logs_dir, exist_ok=True)
        log_filepath = os.path.join(logs_dir, "recalculo_temperatura_quina.txt")
        with open(log_filepath, "w", encoding="utf-8") as f:
            f.write("")

        analisar_tendencias(log_filepath, db)
        return {"status": "success", "message": "Temperaturas e tendências da Quina recalculadas com sucesso."}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/gerar-fechamento")
def endpoint_gerar_fechamento_quina(req: GerarFechamentoQuinaRequest):
    try:
        from lotofacil.main import engine_app

        resultado = gerar_jogos_fechamento_quina(
            db=None,
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
        import traceback
        import logging
        logging.error(f"Erro em /quina/gerar-fechamento: {traceback.format_exc()}")
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/setup-tables")
@router.post("/setup-tables")
def setup_tables():
    try:
        init_databases()
        Base.metadata.create_all(bind=engine)
        garantir_colunas_temperatura_quina(engine)
        from sqlalchemy import inspect
        tables = inspect(engine).get_table_names()
        return {"status": "success", "message": "Tabelas da Quina verificadas/criadas com sucesso", "tables": tables}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
