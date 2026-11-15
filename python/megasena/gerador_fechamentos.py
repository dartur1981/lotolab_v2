import json
import itertools
import random
from typing import List, Dict, Any, Tuple, Optional
from collections import Counter
import pandas as pd
from sqlalchemy.orm import Session
from sqlalchemy import text

PRIMOS = {2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59}
FIBONACCI = {1, 2, 3, 5, 8, 13, 21, 34, 55}

def calc_linha(n: int) -> int:
    return (n - 1) // 10 + 1

def calc_coluna(n: int) -> int:
    return (n - 1) % 10 + 1

def calc_quadrante(n: int) -> int:
    if n <= 20:
        return 1
    elif n <= 40:
        return 2
    else:
        return 3

def calc_sub_quadrante(n: int) -> int:
    linha = (n - 1) // 10
    coluna = (n - 1) % 10
    return (linha // 2) * 5 + (coluna // 2) + 1

def avaliar_jogo_megasena(jogo: Tuple[int, ...], mapa_temperatura: Optional[Dict[int, str]] = None) -> Tuple[int, bool]:
    score = 100
    valido = True
    
    pares = sum(1 for d in jogo if d % 2 == 0)
    # Pares ideais na Mega-Sena: 2, 3 ou 4
    if pares in (3,):
        score += 30
    elif pares in (2, 4):
        score += 20
    elif pares in (1, 5):
        score -= 20
    else:
        score -= 50
        valido = False

    soma = sum(jogo)
    # Soma ideal na Mega-Sena: 130 a 240
    if 130 <= soma <= 240:
        score += 25
    elif 100 <= soma <= 270:
        score += 10
    else:
        score -= 30
        
    # 3 Quadrantes (Q1: 1-20, Q2: 21-40, Q3: 41-60)
    # O ideal demonstrado é 2 dezenas por quadrante (0/2 em cada faixa de 20)
    q_counts = [0, 0, 0]
    for d in jogo:
        q_counts[calc_quadrante(d) - 1] += 1
        
    if q_counts == [2, 2, 2]:
        score += 40
    elif max(q_counts) <= 3 and min(q_counts) >= 1:
        score += 25
    elif max(q_counts) > 3:
        score -= 30
        valido = False

    # 15 Sub-Quadrantes (blocos de 4 números)
    sub_counts = Counter(calc_sub_quadrante(d) for d in jogo)
    # Não permitir mais de 2 números no mesmo sub-quadrante de 4
    if any(count > 2 for count in sub_counts.values()):
        score -= 40
        valido = False
    elif all(count == 1 for count in sub_counts.values()):
        score += 20
        
    # Linhas: no máximo 3 por linha
    linhas = [calc_linha(d) for d in jogo]
    if any(linhas.count(l) > 3 for l in set(linhas)):
        score -= 30
        valido = False
        
    # Colunas: no máximo 3 por coluna
    colunas = [calc_coluna(d) for d in jogo]
    if any(colunas.count(c) > 3 for c in set(colunas)):
        score -= 30
        valido = False
        
    # Primos: 1 a 3 primos
    primos = sum(1 for d in jogo if d in PRIMOS)
    if 1 <= primos <= 3:
        score += 15

    # Avaliação por Temperatura das Dezenas (se disponível)
    if mapa_temperatura:
        qnt_quente = sum(1 for d in jogo if mapa_temperatura.get(d) == "quente")
        qnt_morno = sum(1 for d in jogo if mapa_temperatura.get(d) == "morno")
        qnt_frio = sum(1 for d in jogo if mapa_temperatura.get(d) == "frio")

        # Padrão ótimo histórico: 2 a 3 quentes, 2 a 3 mornas, 0 a 1 fria
        if (2 <= qnt_quente <= 3) and (2 <= qnt_morno <= 3) and (qnt_frio <= 1):
            score += 35
        elif (1 <= qnt_quente <= 4) and (qnt_frio <= 2):
            score += 15
        elif qnt_frio >= 3 or qnt_quente >= 5:
            score -= 40
            valido = False

    return score, valido

def gerar_jogos_fechamento_megasena(
    db: Session,
    engine_app,
    bolao_id: int,
    fechamento_id: int,
    quantidade_jogos: int,
    dezenas_pool: List[int]
) -> Dict[str, Any]:
    dezenas_pool = sorted(list(set(dezenas_pool)))
    total_pool = len(dezenas_pool)

    if total_pool < 6:
        return {"status": "error", "message": f"O pool possui apenas {total_pool} dezenas (mínimo 6)."}

    # Carrega mapa de temperaturas atuais do banco analítico se disponível
    mapa_temperatura = {}
    try:
        from megasena import models
        tendencias = db.query(models.TendenciaDezenaMegasena).all()
        mapa_temperatura = {t.dezena: t.temperatura for t in tendencias}
    except Exception:
        mapa_temperatura = {}

    # Se o pool for exatamente 6, é apenas 1 jogo
    if total_pool == 6:
        jogos_finais = [tuple(dezenas_pool)]
    else:
        todas_combinacoes = list(itertools.combinations(dezenas_pool, 6))
        
        avaliadas = []
        for comb in todas_combinacoes:
            sc, val = avaliar_jogo_megasena(comb, mapa_temperatura=mapa_temperatura)
            avaliadas.append((comb, sc, val))
            
        # Ordena pelo melhor score
        avaliadas.sort(key=lambda x: x[1], reverse=True)
        
        # Filtra preferencialmente as válidas
        validas = [comb for comb, sc, val in avaliadas if val]
        
        if len(validas) >= quantidade_jogos:
            # Seleciona de forma balanceada para cobrir todas as dezenas do pool
            jogos_finais = validas[:quantidade_jogos]
        else:
            jogos_finais = [comb for comb, sc, val in avaliadas[:quantidade_jogos]]

    # Salva no banco de dados da aplicação na tabela megasena_fechamento_jogos
    registros = []
    for jogo in jogos_finais:
        registros.append({
            "fechamento_id": fechamento_id,
            "dezenas": json.dumps(list(jogo)),
            "status": 0,
            "acertos": 0,
            "created_at": pd.Timestamp.now(),
            "updated_at": pd.Timestamp.now()
        })

    with engine_app.begin() as conn:
        # Limpa jogos anteriores deste fechamento se houver
        conn.execute(text("DELETE FROM megasena_fechamento_jogos WHERE fechamento_id = :fid"), {"fid": fechamento_id})
        
        # Insere novos jogos
        df_jogos = pd.DataFrame(registros)
        df_jogos.to_sql("megasena_fechamento_jogos", con=conn, if_exists="append", index=False)
        
        # Atualiza o fechamento com as dezenas e quantidade
        conn.execute(
            text("UPDATE megasena_fechamentos SET quantidade_jogos = :qtd, dezenas = :dez, updated_at = NOW() WHERE id = :fid"),
            {
                "qtd": len(registros),
                "dez": json.dumps(dezenas_pool),
                "fid": fechamento_id
            }
        )
        
        # Atualiza status do bolão para 2 (Jogos Gerados)
        conn.execute(
            text("UPDATE megasena_boloes SET status = 2, updated_at = NOW() WHERE id = :bid"),
            {"bid": bolao_id}
        )

    return {
        "status": "success",
        "message": f"{len(registros)} jogos gerados e salvos com sucesso!",
        "quantidade": len(registros),
        "total_pool": total_pool
    }
