import itertools
import pandas as pd
from sqlalchemy.orm import Session
from sqlalchemy import text
from typing import Dict, Any

import models

def sincronizar_estatisticas_avancadas(db: Session, engine) -> Dict[str, Any]:
    """
    Varre todo o histórico de sorteios da Lotofácil, calcula as Trincas Casadas e os padrões globais,
    e atualiza as tabelas lotofacil_estatisticas_trincas e lotofacil_estatisticas_padroes.
    """
    
    # 1. Busca todos os resultados
    resultados = db.query(models.ResultadoLotofacil).order_by(models.ResultadoLotofacil.concurso.asc()).all()
    total_sorteios = len(resultados)
    
    if total_sorteios == 0:
        return {"status": "error", "message": "Nenhum sorteio encontrado no banco."}
        
    frequencia_trincas = {}
    frequencia_duplas = {}
    frequencia_pares = {} # Apenas para padrões 7/8
    
    atraso_soma_ideal = 0
    atraso_pares_ideal = 0
    atraso_fibonacci_ideal = 0
    atraso_moldura_ideal = 0
    
    FIBONACCI = {1, 2, 3, 5, 8, 13, 21}
    MOLDURA = {1, 2, 3, 4, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, 25}
    
    # 2. Processamento concurso a concurso
    for r in resultados:
        # Extrai os números
        dezenas = [
            r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5,
            r.bola_6, r.bola_7, r.bola_8, r.bola_9, r.bola_10,
            r.bola_11, r.bola_12, r.bola_13, r.bola_14, r.bola_15
        ]
        dezenas.sort()
        
        # --- A. TRINCAS ---
        # Gera todas as combinações de 3 elementos dentre as 15 dezenas sorteadas (C(15,3) = 455 trincas por sorteio)
        trincas_sorteio = list(itertools.combinations(dezenas, 3))
        for t in trincas_sorteio:
            chave = f"{t[0]},{t[1]},{t[2]}"
            frequencia_trincas[chave] = frequencia_trincas.get(chave, 0) + 1
            
        # --- A2. DUPLAS (CASADAS) ---
        duplas_sorteio = list(itertools.combinations(dezenas, 2))
        for d in duplas_sorteio:
            chave_dupla = f"{d[0]},{d[1]}"
            frequencia_duplas[chave_dupla] = frequencia_duplas.get(chave_dupla, 0) + 1
            
        # --- B. PADRÕES E ATRASOS ---
        soma = sum(dezenas)
        if 180 <= soma <= 210:
            atraso_soma_ideal = 0
        else:
            atraso_soma_ideal += 1
            
        pares = sum(1 for d in dezenas if d % 2 == 0)
        impares = 15 - pares
        if (pares == 7 and impares == 8) or (pares == 8 and impares == 7):
            atraso_pares_ideal = 0
            frequencia_pares['7/8 ou 8/7'] = frequencia_pares.get('7/8 ou 8/7', 0) + 1
        else:
            atraso_pares_ideal += 1
            
        fib = sum(1 for d in dezenas if d in FIBONACCI)
        if 3 <= fib <= 5:
            atraso_fibonacci_ideal = 0
        else:
            atraso_fibonacci_ideal += 1
            
        moldura = sum(1 for d in dezenas if d in MOLDURA)
        if 9 <= moldura <= 10:
            atraso_moldura_ideal = 0
        else:
            atraso_moldura_ideal += 1

    # 3. Preparando os dados para Inserção no Banco (Trincas)
    # Pega apenas o TOP 1000 trincas para não encher o banco de dados atoa
    trincas_ordenadas = sorted(frequencia_trincas.items(), key=lambda item: item[1], reverse=True)[:1000]
    
    agora = pd.Timestamp.now()
    records_trincas = []
    for dezenas_str, freq in trincas_ordenadas:
        percentual = (freq / total_sorteios) * 100.0
        records_trincas.append({
            "dezenas": dezenas_str,
            "frequencia": freq,
            "percentual": round(percentual, 2),
            "created_at": agora,
            "updated_at": agora
        })
        
    # Prepara as Duplas
    duplas_ordenadas = sorted(frequencia_duplas.items(), key=lambda item: item[1], reverse=True)
    records_duplas = []
    for dezenas_str, freq in duplas_ordenadas:
        d1, d2 = map(int, dezenas_str.split(','))
        percentual = (freq / total_sorteios) * 100.0
        
        # Mapeamento duplo (ida e volta) para busca instantânea no banco
        records_duplas.append({
            "dezena_1": d1,
            "dezena_2": d2,
            "frequencia": freq,
            "percentual": round(percentual, 2),
            "created_at": agora,
            "updated_at": agora
        })
        records_duplas.append({
            "dezena_1": d2,
            "dezena_2": d1,
            "frequencia": freq,
            "percentual": round(percentual, 2),
            "created_at": agora,
            "updated_at": agora
        })
        
    # Limpa a tabela antiga
    with engine.begin() as conn:
        conn.execute(text("TRUNCATE TABLE lotofacil_estatisticas_trincas"))
        conn.execute(text("TRUNCATE TABLE lotofacil_estatisticas_duplas"))
        conn.execute(text("TRUNCATE TABLE lotofacil_estatisticas_padroes"))
        
    df_trincas = pd.DataFrame(records_trincas)
    df_trincas.to_sql('lotofacil_estatisticas_trincas', con=engine, if_exists='append', index=False)
    
    df_duplas = pd.DataFrame(records_duplas)
    df_duplas.to_sql('lotofacil_estatisticas_duplas', con=engine, if_exists='append', index=False)
    
    # 4. Preparando os dados para Inserção no Banco (Padrões)
    padroes = [
        {"padrao": "Soma entre 180 e 210", "valor": 0, "atraso": atraso_soma_ideal, "percentual": 0, "created_at": agora, "updated_at": agora},
        {"padrao": "7 ou 8 Pares", "valor": frequencia_pares.get('7/8 ou 8/7', 0), "atraso": atraso_pares_ideal, "percentual": round((frequencia_pares.get('7/8 ou 8/7', 0)/total_sorteios)*100, 2), "created_at": agora, "updated_at": agora},
        {"padrao": "3 a 5 Fibonacci", "valor": 0, "atraso": atraso_fibonacci_ideal, "percentual": 0, "created_at": agora, "updated_at": agora},
        {"padrao": "9 a 10 Moldura", "valor": 0, "atraso": atraso_moldura_ideal, "percentual": 0, "created_at": agora, "updated_at": agora},
    ]
    
    df_padroes = pd.DataFrame(padroes)
    df_padroes.to_sql('lotofacil_estatisticas_padroes', con=engine, if_exists='append', index=False)

    return {
        "status": "ok",
        "message": "Estatísticas avançadas atualizadas com sucesso."
    }
