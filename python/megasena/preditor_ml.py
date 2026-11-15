import pandas as pd
import numpy as np
import joblib
import os
from sqlalchemy.orm import Session
from megasena import models

MODEL_PATH = os.path.join(os.path.dirname(__file__), "modelo_rf_megasena.pkl")

def prever_proximo_sorteio(db: Session):
    if not os.path.exists(MODEL_PATH):
        return {"status": "error", "message": "Modelo não treinado. Treine a IA primeiro."}
        
    modelo = joblib.load(MODEL_PATH)
    
    resultados = db.query(models.ResultadoMegasena).order_by(models.ResultadoMegasena.concurso.desc()).limit(20).all()
    
    if len(resultados) < 20:
        return {"status": "error", "message": "Histórico insuficiente (precisa de pelo menos 20 sorteios da Mega-Sena)."}
        
    resultados.reverse()
    
    matriz = np.zeros((20, 61), dtype=int)
    for i, r in enumerate(resultados):
        dezenas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5, r.bola_6]
        for d in dezenas:
            if d and 1 <= d <= 60:
                matriz[i][d] = 1
            
    i = 19
    dados_previsao = []
    
    for dezena in range(1, 61):
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
    probabilidades = modelo.predict_proba(df_previsao[['dezena', 'atraso', 'freq_10', 'freq_20', 'saiu_agora']])[:, 1]
    
    previsoes = []
    for idx, row in df_previsao.iterrows():
        dezena = int(row['dezena'])
        prob = float(probabilidades[idx])
        previsoes.append({
            "dezena": dezena,
            "probabilidade": round(prob * 100, 2)
        })
        
    previsoes.sort(key=lambda x: x['probabilidade'], reverse=True)
    
    return {
        "status": "ok",
        "previsoes": previsoes
    }
