import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score
import joblib
import os
from sqlalchemy.orm import Session
from lotofacil import models

MODEL_PATH = os.path.join(os.path.dirname(__file__), "modelo_rf_lotofacil.pkl")

def preparar_dataset(resultados):
    """
    Transforma os resultados brutos em um dataset de machine learning.
    Cada linha será uma combinação de (Concurso, Dezena).
    """
    total_sorteios = len(resultados)
    matriz_sorteios = np.zeros((total_sorteios, 26), dtype=int)
    
    for i, r in enumerate(resultados):
        dezenas = [
            r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5,
            r.bola_6, r.bola_7, r.bola_8, r.bola_9, r.bola_10,
            r.bola_11, r.bola_12, r.bola_13, r.bola_14, r.bola_15
        ]
        for d in dezenas:
            matriz_sorteios[i][d] = 1

    dados = []
    
    for i in range(20, total_sorteios - 1):
        for dezena in range(1, 26):
            atraso = 0
            for j in range(i, -1, -1):
                if matriz_sorteios[j][dezena] == 1:
                    break
                atraso += 1
                
            freq_10 = np.sum(matriz_sorteios[i-9 : i+1, dezena])
            freq_20 = np.sum(matriz_sorteios[i-19 : i+1, dezena])
            saiu_agora = matriz_sorteios[i][dezena]
            target = matriz_sorteios[i+1][dezena]
            
            dados.append({
                'dezena': dezena,
                'atraso': atraso,
                'freq_10': freq_10,
                'freq_20': freq_20,
                'saiu_agora': saiu_agora,
                'TARGET': target
            })
            
    return pd.DataFrame(dados)

def treinar_modelo_rf(db: Session):
    """
    Treina o modelo RandomForest com os dados históricos e salva em disco.
    """
    resultados = db.query(models.ResultadoLotofacil).order_by(models.ResultadoLotofacil.concurso.asc()).all()
    
    if len(resultados) < 50:
        return {"status": "error", "message": "Resultados insuficientes para treinar o modelo."}
        
    df = preparar_dataset(resultados)
    
    X = df[['dezena', 'atraso', 'freq_10', 'freq_20', 'saiu_agora']]
    y = df['TARGET']
    
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    modelo = RandomForestClassifier(n_estimators=100, max_depth=10, random_state=42, n_jobs=1)
    modelo.fit(X_train, y_train)
    
    y_pred = modelo.predict(X_test)
    acuracia = accuracy_score(y_test, y_pred)
    
    joblib.dump(modelo, MODEL_PATH)
    
    return {
        "status": "ok",
        "message": "Modelo treinado com sucesso.",
        "acuracia": round(acuracia * 100, 2),
        "tamanho_dataset": len(df)
    }
