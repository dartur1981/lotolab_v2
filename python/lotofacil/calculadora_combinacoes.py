import time
import numpy as np
import pandas as pd
import itertools
from sqlalchemy.orm import Session
from sqlalchemy import text
from datetime import datetime, timezone, timedelta
from lotofacil import models

BRT = timezone(timedelta(hours=-3))

PRIMOS = {2, 3, 5, 7, 11, 13, 17, 19, 23}
FIBONACCI = {1, 2, 3, 5, 8, 13, 21}
MOLDURA = {1, 2, 3, 4, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, 25}
MIOLO = {7, 8, 9, 12, 13, 14, 17, 18, 19}

# Perfil vencedor das 18 dezenas (calibrado com base no histórico de 58.414 combinações com 15 pontos)
FIXAS = {10, 11, 20, 22, 25}      # Top 5 dezenas mais frequentes (todas acima de 45.200 aparições)
REJEITADAS = {2, 3, 4, 5, 7}       # Top 5 dezenas menos frequentes (todas abaixo de 44.000 aparições)


def calcular_score_18(comb: tuple, ultimos_sorteios: list[list[int]]) -> tuple[int, int, int, int]:
    """
    Calcula o score de uma combinação de 18 dezenas e as repetições
    com os últimos 3 concursos com base na base estatística de 58.414 linhas vencedoras.

    Retorna: (score, repetidas_ant1, repetidas_ant2, repetidas_ant3)
    """
    comb_set = set(comb)
    score = 0

    # 1. Pares e Ímpares (Média: 9,01 pares / 8,99 ímpares | Moda: 9x9)
    pares = sum(1 for b in comb if b % 2 == 0)
    impares = 18 - pares
    if pares == 9 and impares == 9:
        score += 20
    elif (pares == 8 and impares == 10) or (pares == 10 and impares == 8):
        score += 10

    # 2. Números Primos (Média: 6,56 | Moda: 7 primos)
    qtd_primos = len(comb_set & PRIMOS)
    if qtd_primos == 7:
        score += 15
    elif qtd_primos == 6:
        score += 10

    # 3. Fibonacci (Média: 4,27 | Moda: 4)
    qtd_fibo = len(comb_set & FIBONACCI)
    if qtd_fibo == 4:
        score += 15

    # 4. Moldura vs Miolo (Média: 11,10 moldura / 6,90 miolo | Moda: 11x7)
    qtd_moldura = len(comb_set & MOLDURA)
    if qtd_moldura == 11:
        score += 15
    elif qtd_moldura in (10, 12):
        score += 8

    # 5. Soma das Dezenas (Média: 248 | Moda: 247 | Tolerância 240 a 255)
    soma = sum(comb)
    if 240 <= soma <= 255:
        score += 10

    # 6. Dezenas Fixas (Top 5 mais frequentes: 10, 11, 20, 22, 25)
    score += len(comb_set & FIXAS) * 5      # até +25

    # 7. Dezenas Rejeitadas (Top 5 menos frequentes: 02, 03, 04, 05, 07)
    score -= len(comb_set & REJEITADAS) * 5  # até -25

    score = max(0, score)

    # 8. Repetições dos 3 últimos concursos (Moda: 11 dezenas repetidas em todos os 3)
    repetidas = []
    for i, sorteio in enumerate(ultimos_sorteios[:3]):
        rep = len(comb_set & set(sorteio))
        repetidas.append(rep)
        # Bônus máximo se acertar a moda exata (11) ou faixa ótima (10 a 12)
        if rep == 11:
            score += 15 if i == 0 else 8
        elif rep in (10, 12):
            score += 10 if i == 0 else 5

    # Preenche com None se não houver 3 concursos disponíveis
    while len(repetidas) < 3:
        repetidas.append(None)

    return score, repetidas[0], repetidas[1], repetidas[2]

def log_msg(log_filepath, msg):
    timestamp = datetime.now(BRT).strftime("%Y-%m-%d %H:%M:%S")
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

def processar_combinacoes_background(log_filepath: str, engine, draws_history, sorteio_simulacao=None):
    """
    draws_history: lista de listas com os 15 números dos sorteios reais (excluindo 9999).
    sorteio_simulacao: lista com os 15 números do concurso 9999 (se houver).
    """
    try:
        log_msg(log_filepath, "Iniciando processo em background: Gerando 480.700 combinações...")
        start_total = time.time()
        
        # 1. Gerar combinações (18 de 25)
        combs = list(itertools.combinations(range(1, 26), 18))
        
        # 2. Criar matriz de combinações numpy (booleana para dot product rápido)
        # Tamanho: (480700, 25)
        comb_matrix = np.zeros((len(combs), 25), dtype=np.bool_)
        for i, c in enumerate(combs):
            indices = [x - 1 for x in c]
            comb_matrix[i, indices] = True
            
        # 3. Preparar Matriz de Simulação (Concurso 9999)
        if sorteio_simulacao and len(sorteio_simulacao) == 15:
            log_msg(log_filepath, f"Usando Concurso 9999 como gabarito de simulação: {sorteio_simulacao}")
            sorteio_alvo = sorteio_simulacao
        elif len(draws_history) > 0:
            log_msg(log_filepath, "Concurso 9999 não informado. Usando último concurso real como referência de simulação.")
            sorteio_alvo = draws_history[-1]
        else:
            sorteio_alvo = []

        draws_sim = np.zeros((1, 25), dtype=np.bool_)
        if len(sorteio_alvo) == 15:
            indices = [x - 1 for x in sorteio_alvo]
            draws_sim[0, indices] = True

        # 4. Preparar Matriz do Histórico Real Completo
        num_sorteios_reais = len(draws_history)
        log_msg(log_filepath, f"Matriz gerada. Calculando simulação (9999) e histórico contra {num_sorteios_reais} sorteios reais...")
        
        draws_hist = np.zeros((num_sorteios_reais, 25), dtype=np.bool_)
        for idx, d in enumerate(draws_history):
            indices = [x - 1 for x in d]
            draws_hist[idx, indices] = True
            
        # 5. Avaliar performance (em chunks para eficiência e economia de RAM)
        chunk_size = 50000
        acertos_15 = np.zeros(len(combs), dtype=np.int32)
        acertos_14 = np.zeros(len(combs), dtype=np.int32)
        acertos_13 = np.zeros(len(combs), dtype=np.int32)
        acertos_12 = np.zeros(len(combs), dtype=np.int32)
        acertos_11 = np.zeros(len(combs), dtype=np.int32)

        historico_15 = np.zeros(len(combs), dtype=np.int32)
        historico_14 = np.zeros(len(combs), dtype=np.int32)
        historico_13 = np.zeros(len(combs), dtype=np.int32)
        historico_12 = np.zeros(len(combs), dtype=np.int32)
        historico_11 = np.zeros(len(combs), dtype=np.int32)
        
        for i in range(0, len(combs), chunk_size):
            chunk = comb_matrix[i:i+chunk_size]
            chunk_int8 = chunk.astype(np.int8)

            # Acertos na Simulação (Concurso 9999)
            if len(sorteio_alvo) == 15:
                hits_sim = np.dot(chunk_int8, draws_sim.T.astype(np.int8))
                acertos_15[i:i+chunk_size] = np.sum(hits_sim == 15, axis=1)
                acertos_14[i:i+chunk_size] = np.sum(hits_sim == 14, axis=1)
                acertos_13[i:i+chunk_size] = np.sum(hits_sim == 13, axis=1)
                acertos_12[i:i+chunk_size] = np.sum(hits_sim == 12, axis=1)
                acertos_11[i:i+chunk_size] = np.sum(hits_sim == 11, axis=1)

            # Acertos no Histórico Real Completo
            if num_sorteios_reais > 0:
                hits_hist = np.dot(chunk_int8, draws_hist.T.astype(np.int8))
                historico_15[i:i+chunk_size] = np.sum(hits_hist == 15, axis=1)
                historico_14[i:i+chunk_size] = np.sum(hits_hist == 14, axis=1)
                historico_13[i:i+chunk_size] = np.sum(hits_hist == 13, axis=1)
                historico_12[i:i+chunk_size] = np.sum(hits_hist == 12, axis=1)
                historico_11[i:i+chunk_size] = np.sum(hits_hist == 11, axis=1)

            log_msg(log_filepath, f"Avaliado lote {i} a {min(i+chunk_size, len(combs))}...")
            
        log_msg(log_filepath, "Avaliação concluída. Extraindo features e calculando scores com base nos 3 últimos concursos reais...")

        # Pegar últimos 3 sorteios REAIS (ordem decrescente para ant1=mais recente)
        ultimos_3_reais = draws_history[-3:][::-1] if len(draws_history) >= 3 else list(reversed(draws_history))

        # 6. Extrair features e montar dados
        records = []
        for i, c in enumerate(combs):
            pares, impares, primos, fibonacci, moldura, miolo, soma = extrair_features(c)
            score, rep1, rep2, rep3 = calcular_score_18(c, ultimos_3_reais)
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
                "score": score,
                "repetidas_ant1": rep1,
                "repetidas_ant2": rep2,
                "repetidas_ant3": rep3,
                "acertos_15": int(acertos_15[i]),
                "acertos_14": int(acertos_14[i]),
                "acertos_13": int(acertos_13[i]),
                "acertos_12": int(acertos_12[i]),
                "acertos_11": int(acertos_11[i]),
                "historico_15": int(historico_15[i]),
                "historico_14": int(historico_14[i]),
                "historico_13": int(historico_13[i]),
                "historico_12": int(historico_12[i]),
                "historico_11": int(historico_11[i]),
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
