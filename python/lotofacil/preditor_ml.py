import pandas as pd
import numpy as np
import joblib
import os
from sqlalchemy.orm import Session
from lotofacil import models

MODEL_PATH = os.path.join(os.path.dirname(__file__), "modelo_rf_lotofacil.pkl")

def prever_proximo_sorteio(db: Session):
    """
    Carrega o modelo Random Forest salvo e tenta prever as dezenas do próximo sorteio
    baseando-se no estado atual (últimos resultados importados).
    """
    if not os.path.exists(MODEL_PATH):
        return {"status": "error", "message": "Modelo não treinado. Treine a IA primeiro."}
        
    modelo = joblib.load(MODEL_PATH)
    
    # Busca os últimos 20 resultados para montar o "cenário atual"
    resultados = db.query(models.ResultadoLotofacil).order_by(models.ResultadoLotofacil.concurso.desc()).limit(20).all()
    
    if len(resultados) < 20:
        return {"status": "error", "message": "Histórico insuficiente (precisa de pelo menos 20 sorteios)."}
        
    resultados.reverse() # Coloca em ordem cronológica antiga -> mais recente
    
    matriz = np.zeros((20, 26), dtype=int)
    for i, r in enumerate(resultados):
        dezenas = [
            r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5,
            r.bola_6, r.bola_7, r.bola_8, r.bola_9, r.bola_10,
            r.bola_11, r.bola_12, r.bola_13, r.bola_14, r.bola_15
        ]
        for d in dezenas:
            matriz[i][d] = 1
            
    # O cenário atual é o índice 19 (o último sorteio conhecido)
    i = 19
    dados_previsao = []
    
    for dezena in range(1, 26):
        atraso = 0
        for j in range(i, -1, -1):
            if matriz[j][dezena] == 1:
                break
            atraso += 1
            
        freq_10 = np.sum(matriz[i-9 : i+1, dezena])
        freq_20 = np.sum(matriz[i-19 : i+1, dezena])
        saiu_agora = matriz[i][dezena]
        
        dados_previsao.append({
            'dezena': dezena,
            'atraso': atraso,
            'freq_10': freq_10,
            'freq_20': freq_20,
            'saiu_agora': saiu_agora
        })
        
    df_previsao = pd.DataFrame(dados_previsao)
    
    # O classificador retorna a probabilidade da classe 0 e da classe 1
    # Pegamos a probabilidade da classe 1 (a dezena sair no próximo sorteio)
    probabilidades = modelo.predict_proba(df_previsao[['dezena', 'atraso', 'freq_10', 'freq_20', 'saiu_agora']])[:, 1]
    
    previsoes = []
    for idx, row in df_previsao.iterrows():
        dezena = int(row['dezena'])
        prob = float(probabilidades[idx])
        previsoes.append({
            "dezena": dezena,
            "probabilidade": round(prob * 100, 2)
        })
        
    # Ordena as dezenas da maior probabilidade para a menor
    previsoes.sort(key=lambda x: x['probabilidade'], reverse=True)
    
    return {
        "status": "ok",
        "previsoes": previsoes
    }
