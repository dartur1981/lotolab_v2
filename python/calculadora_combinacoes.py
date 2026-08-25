import time
import numpy as np
import pandas as pd
import itertools
from sqlalchemy.orm import Session
from sqlalchemy import text
from datetime import datetime
import models

PRIMOS = {2, 3, 5, 7, 11, 13, 17, 19, 23}
FIBONACCI = {1, 2, 3, 5, 8, 13, 21}
MOLDURA = {1, 2, 3, 4, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, 25}
MIOLO = {7, 8, 9, 12, 13, 14, 17, 18, 19}

def log_msg(log_filepath, msg):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    formatted = f"[{timestamp}] {msg}\n"
    with open(log_filepath, "a", encoding="utf-8") as f:
        f.write(formatted)
    print(formatted.strip())

def extrair_features(comb):
    pares = sum(1 for b in comb if b % 2 == 0)
    impares = 18 - pares
    primos = sum(1 for b in comb if b in PRIMOS)
    fibonacci = sum(1 for b in comb if b in FIBONACCI)
    moldura = sum(1 for b in comb if b in MOLDURA)
    miolo = sum(1 for b in comb if b in MIOLO)
    soma = sum(comb)
    return pares, impares, primos, fibonacci, moldura, miolo, soma

def processar_combinacoes_background(log_filepath: str, engine, draws_history):
    """
    draws_history é uma lista de listas com os 15 números sorteados de todos os concursos.
    Ex: [[1, 2, ...], [2, 3, ...]]
    """
    try:
        log_msg(log_filepath, "Iniciando processo em background: Gerando 480.700 combinações...")
        start_total = time.time()
        
        # 1. Gerar combinações
        combs = list(itertools.combinations(range(1, 26), 18))
        
        # 2. Criar matriz de combinações numpy (booleana para dot product rápido)
        # Tamanho: (480700, 25)
        comb_matrix = np.zeros((len(combs), 25), dtype=np.bool_)
        for i, c in enumerate(combs):
            # c tem valores de 1 a 25, subtraímos 1 para usar como índice (0 a 24)
            indices = [x - 1 for x in c]
            comb_matrix[i, indices] = True
            
        log_msg(log_filepath, f"Matriz de combinações gerada. Avaliando contra {len(draws_history)} sorteios...")
        
        # 3. Criar matriz de sorteios
        draws = np.zeros((len(draws_history), 25), dtype=np.bool_)
        for i, d in enumerate(draws_history):
            indices = [x - 1 for x in d]
            draws[i, indices] = True
            
        # 4. Avaliar performance (em chunks para não estourar RAM)
        chunk_size = 50000
        acertos_15 = np.zeros(len(combs), dtype=np.int32)
        acertos_14 = np.zeros(len(combs), dtype=np.int32)
        acertos_13 = np.zeros(len(combs), dtype=np.int32)
        acertos_12 = np.zeros(len(combs), dtype=np.int32)
        acertos_11 = np.zeros(len(combs), dtype=np.int32)
        
        for i in range(0, len(combs), chunk_size):
            chunk = comb_matrix[i:i+chunk_size]
            # O produto escalar entre combinação (18 bits) e sorteio (15 bits) retorna a qtd de acertos
            hits = np.dot(chunk.astype(np.int8), draws.T.astype(np.int8))
            acertos_15[i:i+chunk_size] = np.sum(hits == 15, axis=1)
            acertos_14[i:i+chunk_size] = np.sum(hits == 14, axis=1)
            acertos_13[i:i+chunk_size] = np.sum(hits == 13, axis=1)
            acertos_12[i:i+chunk_size] = np.sum(hits == 12, axis=1)
            acertos_11[i:i+chunk_size] = np.sum(hits == 11, axis=1)
            log_msg(log_filepath, f"Avaliado lote {i} a {i+chunk_size}...")
            
        log_msg(log_filepath, "Avaliação de performance concluída. Extraindo features das combinações...")
        
        # 5. Extrair features e montar dados
        records = []
        for i, c in enumerate(combs):
            pares, impares, primos, fibonacci, moldura, miolo, soma = extrair_features(c)
            dezenas_str = ",".join(map(str, c))
            records.append({
                "dezenas": dezenas_str,
                "pares": pares,
                "impares": impares,
                "primos": primos,
                "fibonacci": fibonacci,
                "moldura": moldura,
                "miolo": miolo,
                "soma": soma,
                "acertos_15": int(acertos_15[i]),
                "acertos_14": int(acertos_14[i]),
                "acertos_13": int(acertos_13[i]),
                "acertos_12": int(acertos_12[i]),
                "acertos_11": int(acertos_11[i]),
            })
            
        # 6. Salvar no banco de dados com Pandas `to_sql` para altíssima performance em bulk insert
        log_msg(log_filepath, "Gravando combinações no banco de dados (pode demorar alguns segundos)...")
        df = pd.DataFrame(records)
        
        # Limpar tabela anterior (truncate) já que estamos recriando o mapa todo
        with engine.begin() as conn:
            conn.execute(text("TRUNCATE TABLE combinacoes_18"))
            
        df.to_sql(
            name='combinacoes_18', 
            con=engine, 
            if_exists='append', 
            index=False, 
            chunksize=10000, 
            method='multi'
        )
        
        tempo_total = time.time() - start_total
        log_msg(log_filepath, f"Processo em background finalizado com SUCESSO em {tempo_total:.2f} segundos!")
        
    except Exception as e:
        log_msg(log_filepath, f"ERRO no processo de background: {str(e)}")
