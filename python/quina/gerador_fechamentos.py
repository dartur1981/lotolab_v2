import json
import itertools
import random
import math
from typing import List, Dict, Any, Tuple, Optional
from collections import Counter
import pandas as pd
from sqlalchemy.orm import Session
from sqlalchemy import text

# Primos de 1 a 80
PRIMOS_QUINA = {2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79}
FIBONACCI_QUINA = {1, 2, 3, 5, 8, 13, 21, 34, 55}

def calc_linha(n: int) -> int:
    return (n - 1) // 10 + 1  # Linhas 1 a 8

def calc_coluna(n: int) -> int:
    return (n - 1) % 10 + 1  # Colunas 1 a 10

def calc_quadrante_quina(n: int) -> int:
    if n <= 20:
        return 1
    elif n <= 40:
        return 2
    elif n <= 60:
        return 3
    else:
        return 4

def calc_sub_quadrante_quina(n: int) -> int:
    linha = (n - 1) // 10
    coluna = (n - 1) % 10
    return (linha // 2) * 5 + (coluna // 2) + 1

def avaliar_duque_quina(duque: Tuple[int, int]) -> float:
    score = 100.0
    d1, d2 = duque
    if calc_quadrante_quina(d1) != calc_quadrante_quina(d2):
        score += 30.0
    if calc_linha(d1) != calc_linha(d2):
        score += 15.0
    if calc_coluna(d1) != calc_coluna(d2):
        score += 15.0
    # Paridade 1P / 1I
    if (d1 % 2) != (d2 % 2):
        score += 20.0
    soma = d1 + d2
    if 50 <= soma <= 110:
        score += 20.0
    return score

def avaliar_terno_quina(terno: Tuple[int, int, int]) -> float:
    score = 100.0
    quadrantes = set(calc_quadrante_quina(d) for d in terno)
    if len(quadrantes) == 3:
        score += 40.0
    elif len(quadrantes) == 2:
        score += 15.0
    else:
        score -= 25.0

    linhas = set(calc_linha(d) for d in terno)
    if len(linhas) == 3:
        score += 15.0
    colunas = set(calc_coluna(d) for d in terno)
    if len(colunas) == 3:
        score += 15.0

    pares = sum(1 for d in terno if d % 2 == 0)
    if pares in (1, 2):
        score += 20.0
    else:
        score -= 10.0

    soma = sum(terno)
    if 90 <= soma <= 150:
        score += 20.0

    return score

def avaliar_jogo_quina(jogo: Tuple[int, ...]) -> Tuple[int, bool]:
    score = 100
    valido = True

    # Paridade (Ideal: 2P/3I ou 3P/2I)
    pares = sum(1 for d in jogo if d % 2 == 0)
    if pares in (2, 3):
        score += 35
    elif pares in (1, 4):
        score += 15
    else:
        score -= 40
        valido = False

    # Soma ideal na Quina: média ~202.5 -> faixa ideal 140 a 265
    soma = sum(jogo)
    if 140 <= soma <= 265:
        score += 30
    elif 110 <= soma <= 295:
        score += 10
    else:
        score -= 30

    # 4 Quadrantes (Q1: 1-20, Q2: 21-40, Q3: 41-60, Q4: 61-80)
    q_counts = [0, 0, 0, 0]
    for d in jogo:
        q_counts[calc_quadrante_quina(d) - 1] += 1

    quadrantes_com_dezenas = sum(1 for c in q_counts if c > 0)
    if quadrantes_com_dezenas >= 3 and max(q_counts) <= 2:
        score += 40
    elif max(q_counts) >= 4:
        score -= 40
        valido = False

    # Linhas (máx 2 por linha)
    linhas = [calc_linha(d) for d in jogo]
    if any(linhas.count(l) > 2 for l in set(linhas)):
        score -= 30
        valido = False

    # Colunas (máx 2 por coluna)
    colunas = [calc_coluna(d) for d in jogo]
    if any(colunas.count(c) > 2 for c in set(colunas)):
        score -= 30
        valido = False

    # Primos (1 a 3 primos)
    primos = sum(1 for d in jogo if d in PRIMOS_QUINA)
    if 1 <= primos <= 3:
        score += 15

    return score, valido

def selecionar_duques_e_ternos_balanceados(
    dezenas_pool: List[int],
    qtd_jogos: int
) -> Tuple[Dict[str, List[int]], Dict[str, List[int]]]:
    """
    Gera duques e ternos balanceados a partir do pool com penalidade
    de repetição para distribuição uniforme de todas as dezenas.
    """
    total_pool = len(dezenas_pool)
    todos_duques = list(itertools.combinations(dezenas_pool, 2))
    todos_ternos = list(itertools.combinations(dezenas_pool, 3))

    duques_pontuados = [(d, avaliar_duque_quina(d)) for d in todos_duques]
    ternos_pontuados = [(t, avaliar_terno_quina(t)) for t in todos_ternos]

    random.seed(42)
    random.shuffle(duques_pontuados)
    random.shuffle(ternos_pontuados)

    duques_pontuados.sort(key=lambda x: x[1], reverse=True)
    ternos_pontuados.sort(key=lambda x: x[1], reverse=True)

    # Precisamos de quantidade suficiente de duques e ternos
    qtd_duques = max(8, int(math.ceil(math.sqrt(qtd_jogos * 2))) + 2)
    qtd_ternos = max(8, int(math.ceil(math.sqrt(qtd_jogos * 2))) + 2)

    dezenas_cobertas: Counter = Counter({d: 0 for d in dezenas_pool})

    # Seleciona duques com rotação
    sel_duques = []
    while len(sel_duques) < qtd_duques and duques_pontuados:
        melhor = None
        melhor_sc = -999999.0
        melhor_idx = -1
        janela = min(len(duques_pontuados), 300)
        for idx in range(janela):
            d, sc = duques_pontuados[idx]
            rep = sum(dezenas_cobertas[num] for num in d)
            sc_adj = sc - (rep * 30.0)
            if sc_adj > melhor_sc:
                melhor_sc = sc_adj
                melhor = d
                melhor_idx = idx

        if melhor is not None:
            sel_duques.append(melhor)
            for num in melhor:
                dezenas_cobertas[num] += 1
            duques_pontuados.pop(melhor_idx)
        else:
            break

    # Seleciona ternos com rotação
    sel_ternos = []
    while len(sel_ternos) < qtd_ternos and ternos_pontuados:
        melhor = None
        melhor_sc = -999999.0
        melhor_idx = -1
        janela = min(len(ternos_pontuados), 400)
        for idx in range(janela):
            t, sc = ternos_pontuados[idx]
            rep = sum(dezenas_cobertas[num] for num in t)
            sc_adj = sc - (rep * 35.0)
            if sc_adj > melhor_sc:
                melhor_sc = sc_adj
                melhor = t
                melhor_idx = idx

        if melhor is not None:
            sel_ternos.append(melhor)
            for num in melhor:
                dezenas_cobertas[num] += 1
            ternos_pontuados.pop(melhor_idx)
        else:
            break

    dict_duques = {f"D{idx}": sorted(list(d)) for idx, d in enumerate(sel_duques, 1)}
    dict_ternos = {f"T{idx}": sorted(list(t)) for idx, t in enumerate(sel_ternos, 1)}

    return dict_duques, dict_ternos

def gerar_jogos_fechamento_quina(
    db: Session,
    engine_app,
    bolao_id: int,
    fechamento_id: int,
    quantidade_jogos: int,
    dezenas_pool: List[int]
) -> Dict[str, Any]:
    dezenas_pool = sorted(list(set(dezenas_pool)))
    total_pool = len(dezenas_pool)

    if total_pool < 5:
        return {"status": "error", "message": f"O pool possui apenas {total_pool} dezenas (mínimo 5)."}

    # Se o pool for exatamente 5 dezenas
    if total_pool == 5:
        d1 = dezenas_pool[:2]
        t1 = dezenas_pool[2:]
        dict_grupos = {"D1": d1, "T1": t1}
        jogos_finais = [(tuple(dezenas_pool), "D1 + T1", 150)]
    else:
        dict_duques, dict_ternos = selecionar_duques_e_ternos_balanceados(dezenas_pool, quantidade_jogos)
        dict_grupos = {**dict_duques, **dict_ternos}

        candidatos_jogos = []
        for d_nome, d_vals in dict_duques.items():
            set_d = set(d_vals)
            for t_nome, t_vals in dict_ternos.items():
                set_t = set(t_vals)
                if set_d.isdisjoint(set_t):
                    jogo = tuple(sorted(list(set_d | set_t)))
                    label = f"{d_nome} + {t_nome}"
                    sc, val = avaliar_jogo_quina(jogo)
                    candidatos_jogos.append((jogo, label, sc, val))

        random.seed(bolao_id or 42)
        random.shuffle(candidatos_jogos)
        candidatos_jogos.sort(key=lambda x: (x[3], x[2]), reverse=True)

        jogos_vistos = set()
        jogos_finais = []
        for jogo, label, sc, val in candidatos_jogos:
            if jogo not in jogos_vistos:
                jogos_vistos.add(jogo)
                jogos_finais.append((jogo, label, sc))
                if len(jogos_finais) >= quantidade_jogos:
                    break

        if len(jogos_finais) < quantidade_jogos:
            todas_comb = list(itertools.combinations(dezenas_pool, 5))
            random.seed(bolao_id or 42)
            random.shuffle(todas_comb)
            sobras = []
            for comb in todas_comb:
                if comb not in jogos_vistos:
                    sc, val = avaliar_jogo_quina(comb)
                    sobras.append((comb, sc, val))
            sobras.sort(key=lambda x: (x[2], x[1]), reverse=True)

            for comb, sc, val in sobras:
                jogos_finais.append((comb, "Comb. Grupos", sc))
                jogos_vistos.add(comb)
                if len(jogos_finais) >= quantidade_jogos:
                    break

    # Salva no banco de dados na tabela quina_fechamento_jogos
    registros = []
    for jogo, label_grupos, sc in jogos_finais:
        registros.append({
            "fechamento_id": fechamento_id,
            "dezenas": json.dumps(list(jogo)),
            "grupos": label_grupos,
            "status": 0,
            "acertos": 0,
            "created_at": pd.Timestamp.now(),
            "updated_at": pd.Timestamp.now()
        })

    with engine_app.begin() as conn:
        conn.execute(text("DELETE FROM quina_fechamento_jogos WHERE fechamento_id = :fid"), {"fid": fechamento_id})
        df_jogos = pd.DataFrame(registros)
        df_jogos.to_sql("quina_fechamento_jogos", con=conn, if_exists="append", index=False)

        conn.execute(
            text("UPDATE quina_fechamentos SET quantidade_jogos = :qtd, dezenas = :dez, grupos = :grp, updated_at = NOW() WHERE id = :fid"),
            {
                "qtd": len(registros),
                "dez": json.dumps(dezenas_pool),
                "grp": json.dumps(dict_grupos),
                "fid": fechamento_id
            }
        )

        conn.execute(
            text("UPDATE quina_boloes SET status = 2, updated_at = NOW() WHERE id = :bid"),
            {"bid": bolao_id}
        )

    return {
        "status": "success",
        "message": f"{len(registros)} jogos gerados pela matriz da Quina e salvos com sucesso!",
        "quantidade": len(registros),
        "total_pool": total_pool,
        "grupos": dict_grupos
    }
