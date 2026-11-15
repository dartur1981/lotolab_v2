import datetime
import json
import pandas as pd
from sqlalchemy.orm import Session
from sqlalchemy.dialects.mysql import insert
from megasena import models
from megasena.classificador_temperatura import calcular_temperatura_atual

def analisar_tendencias(log_filepath: str, db: Session):
    def log_msg(msg):
        timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        formatted = f"[{timestamp}] {msg}\n"
        with open(log_filepath, "a", encoding="utf-8") as f:
            f.write(formatted)
        print(formatted.strip())

    try:
        log_msg("Iniciando cálculo rigoroso de temperatura e tendências da Mega-Sena...")
        
        # Carrega histórico completo ordenado por concurso decrescente
        historico_completo = db.query(models.ResultadoMegasena).filter(
            models.ResultadoMegasena.concurso != 9999
        ).order_by(models.ResultadoMegasena.concurso.desc()).all()
        
        if not historico_completo:
            log_msg("Sem histórico para analisar tendências da Mega-Sena.")
            return

        # Prepara DataFrame para o classificador de temperatura
        dados_sorteios = [
            {
                "concurso": r.concurso,
                "bola_1": r.bola_1,
                "bola_2": r.bola_2,
                "bola_3": r.bola_3,
                "bola_4": r.bola_4,
                "bola_5": r.bola_5,
                "bola_6": r.bola_6
            }
            for r in historico_completo
        ]
        df_sorteios = pd.DataFrame(dados_sorteios)

        # Executa o algoritmo com as 4 janelas (10, 20, 50, 100) e pesos (0.4, 0.3, 0.2, 0.1)
        df_temp = calcular_temperatura_atual(df_sorteios)
        temp_dict = {row["dezena"]: row for _, row in df_temp.iterrows()}

        # Verifica ciclo aberto
        ciclo_aberto = db.query(models.CicloMegasena).filter_by(status="ABERTO").first()
        pendentes_ciclo = []
        if ciclo_aberto and ciclo_aberto.json_matriz:
            matriz = json.loads(ciclo_aberto.json_matriz)
            pendentes_ciclo = matriz.get("faltantes", [])
            
        log_msg(f"Ciclo da Mega-Sena aguarda {len(pendentes_ciclo)} dezenas pendentes: {pendentes_ciclo}")

        # Lê configuração de janela adicional caso exista (padrão 25 concursos)
        config = db.query(models.ConfiguracaoAppMegasena).filter_by(chave="janela_tendencia").first()
        janela_ref = int(config.valor) if config else 25

        records = []
        for dezena in range(1, 61):
            temp_info = temp_dict.get(dezena, {})
            categoria_temp = temp_info.get("categoria", "morno")
            score_temp = temp_info.get("score_temperatura", 0.0)
            r_10 = temp_info.get("r_10", 0.0)
            r_20 = temp_info.get("r_20", 0.0)
            r_50 = temp_info.get("r_50", 0.0)
            r_100 = temp_info.get("r_100", 0.0)

            # Frequência na janela de referência (últimos N concursos)
            freq_janela = 0
            for r in historico_completo[:janela_ref]:
                bolas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5, r.bola_6]
                if dezena in bolas:
                    freq_janela += 1

            # Atraso desde a última aparição
            atraso = 0
            for r in historico_completo:
                bolas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5, r.bola_6]
                if dezena in bolas:
                    break
                atraso += 1

            status_ciclo = "Pendente" if dezena in pendentes_ciclo else "Sorteada"
            
            # Score de fechamento e recomendação combinada
            score_fechamento = 0
            if status_ciclo == "Pendente":
                score_fechamento += 50
            if categoria_temp == "quente":
                score_fechamento += 30
            elif categoria_temp == "frio":
                score_fechamento -= 20
                
            if status_ciclo == "Pendente" and categoria_temp == "quente":
                recomendacao = "Alta Potência"
                score_fechamento += 20
            elif status_ciclo == "Pendente" and len(pendentes_ciclo) <= 5:
                recomendacao = "Alta Potência"
                score_fechamento += 30
            elif status_ciclo == "Sorteada" and categoria_temp == "frio":
                recomendacao = "Baixa Potência"
            elif status_ciclo == "Pendente":
                recomendacao = "Alta Potência"
            else:
                recomendacao = "Coringa"
                
            score_fechamento += max(0, 30 - atraso)

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
                "status_ciclo": status_ciclo,
                "recomendacao": recomendacao,
                "score": score_fechamento
            })

        log_msg("Persistindo matriz de tendências e temperaturas no banco analítico da Mega-Sena...")
        for r in records:
            stmt = insert(models.TendenciaDezenaMegasena).values(r)
            stmt = stmt.on_duplicate_key_update(
                frequencia=stmt.inserted.frequencia,
                atraso=stmt.inserted.atraso,
                score_temperatura=stmt.inserted.score_temperatura,
                r_10=stmt.inserted.r_10,
                r_20=stmt.inserted.r_20,
                r_50=stmt.inserted.r_50,
                r_100=stmt.inserted.r_100,
                temperatura=stmt.inserted.temperatura,
                status_ciclo=stmt.inserted.status_ciclo,
                recomendacao=stmt.inserted.recomendacao,
                score=stmt.inserted.score
            )
            db.execute(stmt)
            
        db.commit()
        log_msg("Análise de tendências e temperatura da Mega-Sena concluída com sucesso.")
        
    except Exception as e:
        log_msg(f"ERRO ao analisar tendências da Mega-Sena: {str(e)}")
        raise e
