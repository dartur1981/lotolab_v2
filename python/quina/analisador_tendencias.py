import datetime
import json
import pandas as pd
from sqlalchemy.orm import Session
from sqlalchemy.dialects.mysql import insert
from quina import models
from quina.classificador_temperatura import (
    calcular_temperatura_atual,
    calcular_temperaturas_historicas_sem_lookahead
)

def analisar_tendencias(log_filepath: str, db: Session):
    def log_msg(msg):
        timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        formatted = f"[{timestamp}] {msg}\n"
        with open(log_filepath, "a", encoding="utf-8") as f:
            f.write(formatted)
        print(formatted.strip())

    try:
        log_msg("Iniciando cálculo rigoroso de temperatura e tendências da Quina...")

        # Carrega histórico completo ordenado por concurso decrescente
        historico_completo = db.query(models.ResultadoQuina).filter(
            models.ResultadoQuina.concurso != 9999
        ).order_by(models.ResultadoQuina.concurso.desc()).all()

        if not historico_completo:
            log_msg("Sem histórico para analisar tendências da Quina.")
            return

        # Prepara DataFrame para o classificador de temperatura
        dados_sorteios = [
            {
                "concurso": r.concurso,
                "bola_1": r.bola_1,
                "bola_2": r.bola_2,
                "bola_3": r.bola_3,
                "bola_4": r.bola_4,
                "bola_5": r.bola_5
            }
            for r in historico_completo
        ]
        df_sorteios = pd.DataFrame(dados_sorteios)

        # Executa o algoritmo com as 4 janelas (10, 20, 50, 100)
        df_temp = calcular_temperatura_atual(df_sorteios)
        temp_dict = {row["dezena"]: row for _, row in df_temp.iterrows()}

        janela_ref = 25

        records = []
        for dezena in range(1, 81):
            temp_info = temp_dict.get(dezena, {})
            categoria_temp = temp_info.get("categoria", "morno")
            score_temp = temp_info.get("score_temperatura", 0.0)
            r_10 = temp_info.get("r_10", 0.0)
            r_20 = temp_info.get("r_20", 0.0)
            r_50 = temp_info.get("r_50", 0.0)
            r_100 = temp_info.get("r_100", 0.0)

            # Frequência na janela de referência
            freq_janela = 0
            for r in historico_completo[:janela_ref]:
                bolas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5]
                if dezena in bolas:
                    freq_janela += 1

            # Atraso desde a última aparição
            atraso = 0
            for r in historico_completo:
                bolas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5]
                if dezena in bolas:
                    break
                atraso += 1

            # Score de fechamento
            score_fechamento = 50
            if categoria_temp == "quente":
                score_fechamento += 30
            elif categoria_temp == "frio":
                score_fechamento -= 20

            recomendacao = "Potencial Alto" if categoria_temp == "quente" else ("Regular" if categoria_temp == "morno" else "Atrasada/Fria")

            records.append({
                "dezena": dezena,
                "frequencia": freq_janela,
                "atraso": atraso,
                "score_temperatura": score_temp,
                "r_10": r_10,
                "r_20": r_20,
                "r_50": r_50,
                "r_100": r_100,
                "temperatura": categoria_temp,
                "recomendacao": recomendacao,
                "score": score_fechamento
            })

        log_msg(f"Persistindo tendências de {len(records)} dezenas da Quina no banco...")
        if records:
            stmt = insert(models.TendenciaDezenaQuina).values(records)
            stmt = stmt.on_duplicate_key_update(
                frequencia=stmt.inserted.frequencia,
                atraso=stmt.inserted.atraso,
                score_temperatura=stmt.inserted.score_temperatura,
                r_10=stmt.inserted.r_10,
                r_20=stmt.inserted.r_20,
                r_50=stmt.inserted.r_50,
                r_100=stmt.inserted.r_100,
                temperatura=stmt.inserted.temperatura,
                recomendacao=stmt.inserted.recomendacao,
                score=stmt.inserted.score
            )
            db.execute(stmt)
            db.commit()

        # Calcula temperaturas históricas sem lookahead bias e atualiza features_quina
        log_msg("Atualizando features_quina com métricas de temperatura históricas...")
        df_feat_temp, _ = calcular_temperaturas_historicas_sem_lookahead(df_sorteios)
        temp_feat_dict = df_feat_temp.set_index("concurso").to_dict(orient="index")

        from sqlalchemy import update
        for conc, vals in temp_feat_dict.items():
            db.execute(
                update(models.FeaturesQuina)
                .where(models.FeaturesQuina.concurso == conc)
                .values(
                    temp_quente=vals["temp_quente"],
                    temp_morno=vals["temp_morno"],
                    temp_frio=vals["temp_frio"]
                )
            )
        db.commit()

        log_msg("Tendências e temperaturas das dezenas da Quina calculadas com sucesso!")

    except Exception as e:
        log_msg(f"Erro ao analisar tendências da Quina: {str(e)}")
        db.rollback()
        raise e
