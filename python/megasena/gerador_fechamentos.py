import json
import itertools
import random
import math
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

def avaliar_terno_megasena(terno: Tuple[int, int, int], mapa_temperatura: Optional[Dict[int, str]] = None) -> float:
    """
    Avalia a qualidade estatística de um terno (grupo de 3 dezenas) para a Mega-Sena.
    """
    score = 100.0

    # 1. Distribuição de Quadrantes (Ideal: 1 no Q1, 1 no Q2, 1 no Q3)
    q_counts = [0, 0, 0]
    for d in terno:
        q_counts[calc_quadrante(d) - 1] += 1
    
    if q_counts == [1, 1, 1]:
        score += 45.0  # Perfeito equilíbrio espacial
    elif max(q_counts) == 2:
        score += 15.0  # Razoável (2 em um quadrante, 1 em outro)
    else:
        score -= 35.0  # 3 no mesmo quadrante é desbalanceado

    # 2. Sub-quadrantes (cada dezena em um bloco 2x2 diferente)
    subs = set(calc_sub_quadrante(d) for d in terno)
    if len(subs) == 3:
        score += 20.0
    else:
        score -= 25.0

    # 3. Paridade do Terno (1P/2I ou 2P/1I)
    pares = sum(1 for d in terno if d % 2 == 0)
    if pares in (1, 2):
        score += 20.0
    else:
        score -= 10.0  # Todos pares ou todos ímpares

    # 4. Soma do Terno (Ideal: metade da média 185 -> ~70 a 115)
    soma = sum(terno)
    if 65 <= soma <= 120:
        score += 25.0
    elif 50 <= soma <= 135:
        score += 10.0
    else:
        score -= 20.0

    # 5. Linhas e Colunas
    linhas = set(calc_linha(d) for d in terno)
    colunas = set(calc_coluna(d) for d in terno)
    if len(linhas) == 3:
        score += 10.0
    if len(colunas) == 3:
        score += 10.0

    # 6. Primos
    primos = sum(1 for d in terno if d in PRIMOS)
    if 1 <= primos <= 2:
        score += 10.0

    # 7. Temperaturas (se disponíveis)
    if mapa_temperatura:
        temps = [mapa_temperatura.get(d, 'frio') for d in terno]
        q_quente = temps.count('quente')
        q_morno = temps.count('morno')
        q_frio = temps.count('frio')

        if (1 <= q_quente <= 2) and (1 <= q_morno <= 2):
            score += 25.0
        elif q_quente == 3:
            score -= 15.0
        elif q_frio >= 2:
            score -= 20.0

    return score

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
    # O ideal demonstrado é 2 dezenas por quadrante (2/2/2 em cada faixa de 20)
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

    # Avaliação por Temperatura das Dezenas
    if mapa_temperatura:
        qnt_quente = sum(1 for d in jogo if mapa_temperatura.get(d) == "quente")
        qnt_morno = sum(1 for d in jogo if mapa_temperatura.get(d) == "morno")
        qnt_frio = sum(1 for d in jogo if mapa_temperatura.get(d) == "frio")

        if (2 <= qnt_quente <= 3) and (2 <= qnt_morno <= 3) and (qnt_frio <= 1):
            score += 35
        elif (1 <= qnt_quente <= 4) and (qnt_frio <= 2):
            score += 15
        elif qnt_frio >= 3 or qnt_quente >= 5:
            score -= 40
            valido = False

    return score, valido

def selecionar_melhores_ternos(
    dezenas_pool: List[int],
    qtd_ternos_alvo: int,
    mapa_temperatura: Dict[int, str]
) -> Dict[str, List[int]]:
    """
    Gera todos os ternos candidatos possíveis do pool, pontua cada um e
    seleciona os melhores grupos balanceados de forma a cobrir todo o pool
    uniformemente, evitando concentração repetitiva em poucas dezenas.
    """
    todos_candidatos = list(itertools.combinations(dezenas_pool, 3))
    if not todos_candidatos:
        return {}

    # Pontua cada terno
    ternos_pontuados = []
    for t in todos_candidatos:
        sc = avaliar_terno_megasena(t, mapa_temperatura)
        ternos_pontuados.append((t, sc))
    
    # Embaralha com seed determinística para que empates não beneficiem sempre as menores dezenas
    random.seed(42)
    random.shuffle(ternos_pontuados)
    ternos_pontuados.sort(key=lambda x: x[1], reverse=True)

    # Seleção com rotação e penalidade de repetição para cobertura uniforme de todas as dezenas do pool
    selecionados: List[Tuple[int, int, int]] = []
    dezenas_cobertas: Counter = Counter({d: 0 for d in dezenas_pool})

    while len(selecionados) < qtd_ternos_alvo and ternos_pontuados:
        melhor_terno = None
        melhor_score_ajustado = -999999.0
        melhor_idx = -1
        janela = min(len(ternos_pontuados), 400)

        for idx in range(janela):
            t, sc = ternos_pontuados[idx]
            repeticoes = sum(dezenas_cobertas[d] for d in t)
            # Penaliza dezenas repetidas para forçar a rotação completa do pool
            score_ajustado = sc - (repeticoes * 35.0)
            if score_ajustado > melhor_score_ajustado:
                melhor_score_ajustado = score_ajustado
                melhor_terno = t
                melhor_idx = idx

        if melhor_terno is not None:
            selecionados.append(melhor_terno)
            for d in melhor_terno:
                dezenas_cobertas[d] += 1
            ternos_pontuados.pop(melhor_idx)
        else:
            break

    # Formata como dicionário T1, T2, T3...
    dict_ternos = {}
    for idx, t in enumerate(selecionados, 1):
        dict_ternos[f"T{idx}"] = sorted(list(t))

    return dict_ternos

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

    # Se o pool for exatamente 6 dezenas
    if total_pool == 6:
        t1 = dezenas_pool[:3]
        t2 = dezenas_pool[3:]
        dict_ternos = {"T1": t1, "T2": t2}
        jogos_finais = [(tuple(dezenas_pool), "T1 + T2", 150)]
    else:
        # Calcula quantos ternos são necessários para cobrir a quantidade de jogos
        # Combinação de K ternos 2 a 2 = K*(K-1)/2
        k = 4
        while (k * (k - 1) // 2) < (quantidade_jogos * 2) and k < 20:
            k += 1

        # Seleciona os melhores K ternos com rotação e cobertura equilibrada do pool
        dict_ternos = selecionar_melhores_ternos(dezenas_pool, k, mapa_temperatura)
        chaves_ternos = list(dict_ternos.keys())

        # Combina os ternos 2 a 2 (T_i + T_j)
        candidatos_jogos = []
        for t1_nome, t2_nome in itertools.combinations(chaves_ternos, 2):
            t1_vals = set(dict_ternos[t1_nome])
            t2_vals = set(dict_ternos[t2_nome])

            # Dezenas devem ser disjuntas para formar exatamente 6 números
            if t1_vals.isdisjoint(t2_vals):
                jogo = tuple(sorted(list(t1_vals | t2_vals)))
                label_grupos = f"{t1_nome} + {t2_nome}"
                sc, val = avaliar_jogo_megasena(jogo, mapa_temperatura=mapa_temperatura)
                candidatos_jogos.append((jogo, label_grupos, sc, val))

        # Embaralha os pares candidatos antes da ordenação para desempate diversificado
        random.seed(bolao_id or 42)
        random.shuffle(candidatos_jogos)
        candidatos_jogos.sort(key=lambda x: (x[3], x[2]), reverse=True)

        # Seleciona os melhores jogos sem duplicidade
        jogos_vistos = set()
        jogos_finais = []
        for jogo, label_grupos, sc, val in candidatos_jogos:
            if jogo not in jogos_vistos:
                jogos_vistos.add(jogo)
                jogos_finais.append((jogo, label_grupos, sc))
                if len(jogos_finais) >= quantidade_jogos:
                    break

        # Caso ainda precise de mais jogos para completar a quantidade solicitada
        if len(jogos_finais) < quantidade_jogos:
            todas_combinacoes = list(itertools.combinations(dezenas_pool, 6))
            random.seed(bolao_id or 42)
            random.shuffle(todas_combinacoes)

            sobra_avaliadas = []
            for comb in todas_combinacoes:
                if comb not in jogos_vistos:
                    sc, val = avaliar_jogo_megasena(comb, mapa_temperatura=mapa_temperatura)
                    sobra_avaliadas.append((comb, sc, val))
            sobra_avaliadas.sort(key=lambda x: (x[2], x[1]), reverse=True)

            for comb, sc, val in sobra_avaliadas:
                jogos_finais.append((comb, "Comb. Ternos", sc))
                jogos_vistos.add(comb)
                if len(jogos_finais) >= quantidade_jogos:
                    break

    # Salva no banco de dados na tabela megasena_fechamento_jogos
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
        # Limpa jogos anteriores deste fechamento se houver
        conn.execute(text("DELETE FROM megasena_fechamento_jogos WHERE fechamento_id = :fid"), {"fid": fechamento_id})
        
        # Insere novos jogos com a coluna grupos
        df_jogos = pd.DataFrame(registros)
        df_jogos.to_sql("megasena_fechamento_jogos", con=conn, if_exists="append", index=False)
        
        # Atualiza o fechamento com as dezenas, quantidade e o mapa de grupos/ternos
        conn.execute(
            text("UPDATE megasena_fechamentos SET quantidade_jogos = :qtd, dezenas = :dez, grupos = :grp, updated_at = NOW() WHERE id = :fid"),
            {
                "qtd": len(registros),
                "dez": json.dumps(dezenas_pool),
                "grp": json.dumps(dict_ternos),
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
        "message": f"{len(registros)} jogos gerados por matriz de ternos combinados e salvos com sucesso!",
        "quantidade": len(registros),
        "total_pool": total_pool,
        "ternos": dict_ternos
    }
