import datetime
from sqlalchemy.orm import Session
from sqlalchemy import text
from sqlalchemy.dialects.mysql import insert
import models
import json

def analisar_tendencias(log_filepath: str, db: Session):
    def log_msg(msg):
        timestamp = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        formatted = f"[{timestamp}] {msg}\n"
        with open(log_filepath, "a", encoding="utf-8") as f:
            f.write(formatted)
        print(formatted.strip())

    try:
        log_msg("Lendo configurações de janela de tendências...")
        # Busca a configuração, se não existir, cria padrão 15
        config = db.query(models.ConfiguracaoApp).filter_by(chave="janela_tendencia").first()
        if not config:
            config = models.ConfiguracaoApp(chave="janela_tendencia", valor="15")
            db.add(config)
            db.commit()
            
        janela = int(config.valor)
        log_msg(f"Janela de tendência configurada para os últimos {janela} sorteios.")
        
        # 1. Puxa os últimos N sorteios para calcular frequência e atraso
        ultimos_sorteios = db.query(models.ResultadoLotofacil).order_by(models.ResultadoLotofacil.concurso.desc()).limit(janela).all()
        
        if not ultimos_sorteios:
            log_msg("Sem histórico para analisar tendências.")
            return
            
        # Puxa histórico maior apenas para atraso, caso a dezena não saia nos ultimos 'janela' sorteios
        historico_completo = db.query(models.ResultadoLotofacil).order_by(models.ResultadoLotofacil.concurso.desc()).all()
        
        # 2. Puxa ciclo aberto
        ciclo_aberto = db.query(models.CicloLotofacil).filter_by(status="ABERTO").first()
        pendentes_ciclo = []
        if ciclo_aberto and ciclo_aberto.json_matriz:
            matriz = json.loads(ciclo_aberto.json_matriz)
            pendentes_ciclo = matriz.get("faltantes", [])
            
        log_msg(f"O ciclo atual aguarda as dezenas pendentes: {pendentes_ciclo}")

        records = []
        limite_quente = round(janela * 0.60) # Aproximadamente 60%
        limite_fria = round(janela * 0.40) # Aproximadamente 40%

        for dezena in range(1, 26):
            # Frequencia na janela
            frequencia = 0
            for r in ultimos_sorteios:
                bolas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5, r.bola_6, r.bola_7, r.bola_8, r.bola_9, r.bola_10, r.bola_11, r.bola_12, r.bola_13, r.bola_14, r.bola_15]
                if dezena in bolas:
                    frequencia += 1
                    
            # Atraso em todo o historico
            atraso = 0
            for r in historico_completo:
                bolas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5, r.bola_6, r.bola_7, r.bola_8, r.bola_9, r.bola_10, r.bola_11, r.bola_12, r.bola_13, r.bola_14, r.bola_15]
                if dezena in bolas:
                    break
                atraso += 1

            # Temperatura
            if frequencia >= limite_quente:
                temperatura = "Quente"
            elif frequencia <= limite_fria:
                temperatura = "Fria"
            else:
                temperatura = "Morna"

            # Status Ciclo
            status_ciclo = "Pendente" if dezena in pendentes_ciclo else "Sorteada"
            
            # Score / Recomendacao
            score = 0
            recomendacao = "Coringa"
            
            if status_ciclo == "Pendente":
                score += 50
            if temperatura == "Quente":
                score += 30
            elif temperatura == "Fria":
                score -= 20
                
            if status_ciclo == "Pendente" and temperatura == "Quente":
                recomendacao = "Alta Potência"
                score += 20
            elif status_ciclo == "Pendente" and len(pendentes_ciclo) <= 3:
                # Pivô fortíssimo
                recomendacao = "Alta Potência" 
                score += 30
            elif status_ciclo == "Sorteada" and temperatura == "Fria":
                recomendacao = "Baixa Potência"
            elif status_ciclo == "Pendente":
                recomendacao = "Alta Potência"
            else:
                recomendacao = "Coringa"
                
            records.append({
                "dezena": dezena,
                "frequencia": frequencia,
                "atraso": atraso,
                "temperatura": temperatura,
                "status_ciclo": status_ciclo,
                "recomendacao": recomendacao,
                "score": score
            })
            
        log_msg("Gravando análise de tendências no banco de dados...")
        stmt = insert(models.TendenciaDezena).values(records)
        update_dict = {c.name: c for c in stmt.inserted if c.name != 'dezena'}
        stmt = stmt.on_duplicate_key_update(**update_dict)
        db.execute(stmt)
        db.commit()
        
        log_msg("Tendências atualizadas com sucesso.")

    except Exception as e:
        log_msg(f"Erro na análise de tendências: {str(e)}")
