import json
import itertools
import random
from collections import Counter
from datetime import datetime
from typing import List, Dict, Any, Tuple

import pandas as pd
from sqlalchemy.orm import Session
from sqlalchemy import text

from lotofacil import models
from lotofacil.calculadora_combinacoes import PRIMOS, MOLDURA, FIBONACCI

# Constante de Múltiplos de 3
MULTIPLOS_TRES = {3, 6, 9, 12, 15, 18, 21, 24}

def gerar_jogos_fechamento(
    db: Session,
    engine,
    engine_app,
    bolao_id: int,
    fechamento_id: int,
    quantidade_jogos: int,
    dezenas_pool: List[int]
) -> Dict[str, Any]:
    """
    Motor matemático que gera combinações com rigor estatístico e balanceamento de cobertura,
    garantindo a entrega da quantidade solicitada de jogos e inserindo no banco via Pandas.
    """
    dezenas_pool = sorted(list(set(dezenas_pool)))
    total_pool = len(dezenas_pool)

    if total_pool < 15:
        return {"status": "error", "message": f"O pool possui apenas {total_pool} dezenas (mínimo 15)."}

    # Lê configurações ativas do banco
    configs = {c.chave: c.valor for c in db.query(models.ConfiguracaoApp).all()}

    use_pares_impares = configs.get("filtro_pares_impares", "1") == "1"
    use_primos = configs.get("filtro_primos", "1") == "1"
    use_moldura = configs.get("filtro_moldura", "1") == "1"
    use_posicionais = configs.get("filtro_posicionais", "1") == "1"
    use_casadas = configs.get("filtro_casadas", "0") == "1"
    use_fibonacci = configs.get("filtro_fibonacci", "1") == "1"
    use_multiplos_tres = configs.get("filtro_multiplos_tres", "1") == "1"
    use_sequencias = configs.get("filtro_sequencias", "1") == "1"
    use_soma = configs.get("filtro_soma", "1") == "1"

    afinidades_fortes = []
    if use_casadas:
        records = db.execute(
            text("SELECT dezena_1, dezena_2 FROM lotofacil_estatisticas_duplas ORDER BY frequencia DESC LIMIT 30")
        ).fetchall()
        afinidades_fortes = [(r[0], r[1]) for r in records]

    # Seleção dos jogos com pontuação e cobertura balanceada
    jogos_selecionados, qtd_100_conformes = selecionar_jogos_balanceados(
        dezenas_pool=dezenas_pool,
        quantidade_jogos=quantidade_jogos,
        use_pares=use_pares_impares,
        use_primos=use_primos,
        use_moldura=use_moldura,
        use_posicionais=use_posicionais,
        use_casadas=use_casadas,
        use_fibonacci=use_fibonacci,
        use_multiplos_tres=use_multiplos_tres,
        use_sequencias=use_sequencias,
        use_soma=use_soma,
        afinidades_fortes=afinidades_fortes
    )

    if not jogos_selecionados:
        return {"status": "error", "message": "Nenhum jogo pôde ser gerado a partir do pool fornecido."}

    # Distribuição equilibrada em 3 matrizes (A, B, C) para exibição no formulário Filament
    ranking_quentes = {20: 1, 10: 2, 11: 3, 25: 4, 13: 5, 24: 6, 14: 7, 1: 8, 3: 9, 2: 10}
    dezenas_ordenadas = sorted(dezenas_pool, key=lambda x: ranking_quentes.get(x, 99))

    chunk_size = (total_pool + 2) // 3
    grupo_a = dezenas_ordenadas[0:chunk_size]
    grupo_b = dezenas_ordenadas[chunk_size:chunk_size * 2]
    grupo_c = dezenas_ordenadas[chunk_size * 2:]

    grupos_utilizados = {
        'metodo': f'Desdobramento Estatístico Balanceado ({total_pool} dezenas)',
        'total_jogos_solicitados': quantidade_jogos,
        'jogos_apos_filtros': len(jogos_selecionados),
        'jogos_100_conformes': qtd_100_conformes,
        'grupos': {'A': grupo_a, 'B': grupo_b, 'C': grupo_c}
    }

    # ---------------------------------------------------------
    # Bulk Insert usando Pandas para lotofacil_fechamento_jogos
    # ---------------------------------------------------------
    agora = datetime.now()
    records = []
    for item in jogos_selecionados:
        records.append({
            "fechamento_id": fechamento_id,
            "dezenas": json.dumps(item["jogo"]),
            "status": "0",  # Pendente
            "created_at": agora,
            "updated_at": agora
        })

    df = pd.DataFrame(records)
    df.to_sql(
        name='lotofacil_fechamento_jogos',
        con=engine_app,
        if_exists='append',
        index=False,
        chunksize=2000,
        method='multi'
    )

    # Atualiza metadados do fechamento e o valor do bolão
    try:
        with engine_app.begin() as conn:
            conn.execute(
                text("UPDATE lotofacil_fechamentos SET grupos = :grupos WHERE id = :id"),
                {"grupos": json.dumps(grupos_utilizados), "id": fechamento_id}
            )

            valor_aposta = float(configs.get('valor_aposta', '3.00'))
            valor_total = len(jogos_selecionados) * valor_aposta

            conn.execute(
                text("""
                    UPDATE lotofacil_boloes 
                    SET valor_total = :valor_total, 
                        valor_cota = :valor_total / GREATEST((SELECT COUNT(*) FROM lotofacil_bolao_user WHERE lotofacil_bolao_id = :bolao_id), 1) 
                    WHERE id = :bolao_id
                """),
                {"valor_total": valor_total, "bolao_id": bolao_id}
            )
    except Exception as e:
        print("Erro ao atualizar metadados do fechamento/bolão:", str(e))

    return {
        "status": "ok",
        "message": f"{len(jogos_selecionados)} jogos gerados e salvos com sucesso.",
        "detalhes": grupos_utilizados
    }

def avaliar_jogo(
    jogo: List[int],
    use_pares: bool = True,
    use_primos: bool = True,
    use_moldura: bool = True,
    use_posicionais: bool = True,
    use_casadas: bool = False,
    use_fibonacci: bool = True,
    use_multiplos_tres: bool = True,
    use_sequencias: bool = True,
    use_soma: bool = True,
    afinidades_fortes: List[Tuple[int, int]] = None
) -> Tuple[float, bool]:
    """
    Avalia um jogo de 15 dezenas com base nas regras e filtros estatísticos.
    Retorna (score: float, estritamente_valido: bool).
    """
    score = 0.0
    valido = True

    # 1. Pares e Ímpares (Padrão mais frequente: 7 ou 8 pares)
    pares = sum(1 for n in jogo if n % 2 == 0)
    if pares in (7, 8):
        score += 20.0
    elif pares in (6, 9):
        score += 10.0
        if use_pares:
            valido = False
    else:
        if use_pares:
            valido = False

    # 2. Primos (Padrão mais frequente: 4 a 6 primos)
    primos = sum(1 for n in jogo if n in PRIMOS)
    if 4 <= primos <= 6:
        score += 20.0
    elif primos in (3, 7):
        score += 10.0
        if use_primos:
            valido = False
    else:
        if use_primos:
            valido = False

    # 3. Moldura (Padrão mais frequente: 9 a 10 dezenas)
    moldura = sum(1 for n in jogo if n in MOLDURA)
    if 9 <= moldura <= 10:
        score += 20.0
    elif moldura in (8, 11):
        score += 10.0
        if use_moldura:
            valido = False
    else:
        if use_moldura:
            valido = False

    # 4. Posicionais (Posição 1 <= 3 e Posição 15 >= 23)
    if jogo[0] <= 3 and jogo[-1] >= 23:
        score += 15.0
    elif jogo[0] <= 4 and jogo[-1] >= 22:
        score += 7.0
        if use_posicionais:
            valido = False
    else:
        if use_posicionais:
            valido = False

    # 5. Fibonacci (Padrão: 3 a 5 números)
    fib = sum(1 for n in jogo if n in FIBONACCI)
    if 3 <= fib <= 5:
        score += 10.0
    elif fib in (2, 6):
        score += 5.0
        if use_fibonacci:
            valido = False
    else:
        if use_fibonacci:
            valido = False

    # 6. Múltiplos de 3 (Padrão: 4 a 6 números)
    m3 = sum(1 for n in jogo if n in MULTIPLOS_TRES)
    if 4 <= m3 <= 6:
        score += 10.0
    elif m3 in (3, 7):
        score += 5.0
        if use_multiplos_tres:
            valido = False
    else:
        if use_multiplos_tres:
            valido = False

    # 7. Soma das dezenas (Padrão mais frequente: 180 a 210)
    soma_total = sum(jogo)
    if 180 <= soma_total <= 210:
        score += 15.0
    elif 170 <= soma_total <= 220:
        score += 7.0
        if use_soma:
            valido = False
    else:
        if use_soma:
            valido = False

    # 8. Sequências consecutivas ininterruptas
    max_seq = 1
    current_seq = 1
    for i in range(1, len(jogo)):
        if jogo[i] == jogo[i - 1] + 1:
            current_seq += 1
            if current_seq > max_seq:
                max_seq = current_seq
        else:
            current_seq = 1

    if max_seq <= 5:
        score += 10.0
    elif max_seq <= 7:
        score += 5.0
    else:
        if use_sequencias:
            valido = False

    # 9. Afinidades Fortes (Casadas)
    if afinidades_fortes:
        matches = 0
        for d1, d2 in afinidades_fortes:
            if d1 in jogo and d2 in jogo:
                matches += 1
        if matches > 0:
            score += min(matches * 5.0, 20.0)
        elif use_casadas:
            valido = False

    return score, valido


def selecionar_jogos_balanceados(
    dezenas_pool: List[int],
    quantidade_jogos: int,
    use_pares: bool = True,
    use_primos: bool = True,
    use_moldura: bool = True,
    use_posicionais: bool = True,
    use_casadas: bool = False,
    use_fibonacci: bool = True,
    use_multiplos_tres: bool = True,
    use_sequencias: bool = True,
    use_soma: bool = True,
    afinidades_fortes: List[Tuple[int, int]] = None
) -> Tuple[List[Dict[str, Any]], int]:
    """
    Gera combinações, pontua cada uma e seleciona o número exato de jogos solicitado,
    priorizando jogos 100% conformes com os filtros e completando com jogos de melhor score,
    sempre balanceando a frequência de cada dezena do pool.
    """
    dezenas_pool = sorted(list(set(dezenas_pool)))
    total_pool = len(dezenas_pool)

    if total_pool < 15:
        return [], 0

    # 1. Geração do espaço amostral de combinações
    if total_pool <= 20:
        candidatos = [list(c) for c in itertools.combinations(dezenas_pool, 15)]
    else:
        vistos = set()
        candidatos = []
        for _ in range(30000):
            p = dezenas_pool.copy()
            random.shuffle(p)
            j = tuple(sorted(p[:15]))
            if j not in vistos:
                vistos.add(j)
                candidatos.append(list(j))

    # 2. Avaliação de todos os candidatos
    avaliados = []
    for c in candidatos:
        sc, val = avaliar_jogo(
            c,
            use_pares=use_pares,
            use_primos=use_primos,
            use_moldura=use_moldura,
            use_posicionais=use_posicionais,
            use_casadas=use_casadas,
            use_fibonacci=use_fibonacci,
            use_multiplos_tres=use_multiplos_tres,
            use_sequencias=use_sequencias,
            use_soma=use_soma,
            afinidades_fortes=afinidades_fortes
        )
        avaliados.append({"jogo": c, "score": sc, "valido": val})

    validos = [a for a in avaliados if a["valido"]]
    outros = [a for a in avaliados if not a["valido"]]

    validos.sort(key=lambda x: x["score"], reverse=True)
    outros.sort(key=lambda x: x["score"], reverse=True)

    meta = min(quantidade_jogos, len(candidatos))
    selecionados = []
    freq = Counter({d: 0 for d in dezenas_pool})

    def selecionar_com_cobertura(lista_origem: List[Dict[str, Any]], limite: int):
        nonlocal selecionados, freq
        pool_disp = lista_origem.copy()
        while len(selecionados) < limite and pool_disp:
            def peso_cobertura(item):
                jogo = item["jogo"]
                score_base = item["score"]
                bonus_cobertura = sum(1.0 / (freq[d] + 1) for d in jogo) * 15.0
                return score_base + bonus_cobertura

            pool_disp.sort(key=peso_cobertura, reverse=True)
            escolhido = pool_disp.pop(0)
            selecionados.append(escolhido)
            for d in escolhido["jogo"]:
                freq[d] += 1

    # Prioriza jogos Tier 1 (100% conformes)
    selecionar_com_cobertura(validos, meta)

    # Se ainda faltarem jogos para atingir a meta, preenche com os melhores do Tier 2
    if len(selecionados) < meta:
        selecionar_com_cobertura(outros, meta)

    return selecionados, len(validos)


# Funções legadas mantidas para compatibilidade retroativa
def validar_jogo_unico(
    jogo: List[int],
    use_pares: bool,
    use_primos: bool,
    use_moldura: bool,
    use_posicionais: bool,
    use_casadas: bool,
    use_fibonacci: bool,
    use_multiplos_tres: bool,
    use_sequencias: bool,
    use_soma: bool,
    afinidades_fortes: List[Tuple[int, int]]
) -> bool:
    _, valido = avaliar_jogo(
        jogo,
        use_pares=use_pares,
        use_primos=use_primos,
        use_moldura=use_moldura,
        use_posicionais=use_posicionais,
        use_casadas=use_casadas,
        use_fibonacci=use_fibonacci,
        use_multiplos_tres=use_multiplos_tres,
        use_sequencias=use_sequencias,
        use_soma=use_soma,
        afinidades_fortes=afinidades_fortes
    )
    return valido


def aplicar_filtros(
    jogos: List[List[int]],
    use_pares,
    use_primos,
    use_moldura,
    use_posicionais,
    use_casadas,
    use_fibonacci,
    use_multiplos_tres,
    use_sequencias,
    use_soma,
    afinidades_fortes
) -> List[List[int]]:
    aprovados = []
    for j in jogos:
        if validar_jogo_unico(
            j, use_pares, use_primos, use_moldura, use_posicionais,
            use_casadas, use_fibonacci, use_multiplos_tres, use_sequencias,
            use_soma, afinidades_fortes
        ):
            aprovados.append(j)
    return aprovados


def pontuar_jogo(jogo: List[int], afinidades_fortes: List[Tuple[int, int]] = None) -> float:
    score, _ = avaliar_jogo(jogo, afinidades_fortes=afinidades_fortes)
    return score

def gerar_jogos_estrategia(
    db: Session,
    engine_app,
    fechamento_id: int,
    quantidade_jogos: int,
    dezenas_pool: List[int]
) -> Dict[str, Any]:
    """
    Gera jogos estratégicos avulsos com base no pool de dezenas e salva na tabela correspondente.
    """
    dezenas_pool = sorted(list(set(dezenas_pool)))
    total_pool = len(dezenas_pool)

    if total_pool < 15:
        return {'status': 'error', 'message': f'O pool possui apenas {total_pool} dezenas (mínimo 15).'}

    configs = {}
    try:
        configs = {c.chave: c.valor for c in db.query(models.ConfiguracaoApp).all()}
    except Exception:
        pass

    use_pares_impares = configs.get("filtro_pares_impares", "1") == "1"
    use_primos = configs.get("filtro_primos", "1") == "1"
    use_moldura = configs.get("filtro_moldura", "1") == "1"
    use_posicionais = configs.get("filtro_posicionais", "1") == "1"
    use_casadas = configs.get("filtro_casadas", "0") == "1"
    use_fibonacci = configs.get("filtro_fibonacci", "1") == "1"
    use_multiplos_tres = configs.get("filtro_multiplos_tres", "1") == "1"
    use_sequencias = configs.get("filtro_sequencias", "1") == "1"
    use_soma = configs.get("filtro_soma", "1") == "1"

    afinidades_fortes = []
    try:
        records = db.execute(
            text("SELECT dezena_1, dezena_2 FROM lotofacil_estatisticas_duplas ORDER BY frequencia DESC LIMIT 30")
        ).fetchall()
        afinidades_fortes = [(r[0], r[1]) for r in records]
    except Exception:
        afinidades_fortes = [(1, 2), (24, 25), (3, 4), (13, 14)]

    jogos_selecionados, qtd_100_conformes = selecionar_jogos_balanceados(
        dezenas_pool=dezenas_pool,
        quantidade_jogos=quantidade_jogos,
        use_pares=use_pares_impares,
        use_primos=use_primos,
        use_moldura=use_moldura,
        use_posicionais=use_posicionais,
        use_casadas=use_casadas,
        use_fibonacci=use_fibonacci,
        use_multiplos_tres=use_multiplos_tres,
        use_sequencias=use_sequencias,
        use_soma=use_soma,
        afinidades_fortes=afinidades_fortes
    )

    if not jogos_selecionados:
        return {'status': 'error', 'message': 'Nenhum jogo pôde ser gerado a partir do pool fornecido.'}

    agora = datetime.now()
    records = []
    for item in jogos_selecionados:
        records.append({
            'estrategia_fechamento_id': fechamento_id,
            'dezenas': json.dumps(item["jogo"]),
            'score': int(item["score"]),
            'status': '0',
            'created_at': agora,
            'updated_at': agora
        })

    df = pd.DataFrame(records)
    df.to_sql(
        'estrategia_fechamento_jogos',
        engine_app,
        if_exists='append',
        index=False,
        chunksize=2000,
        method='multi'
    )

    ranking_quentes = {20: 1, 10: 2, 11: 3, 25: 4, 13: 5, 24: 6, 14: 7, 1: 8, 3: 9, 2: 10}
    dezenas_ordenadas = sorted(dezenas_pool, key=lambda x: ranking_quentes.get(x, 99))
    chunk_size = (total_pool + 2) // 3
    grupo_a = dezenas_ordenadas[0:chunk_size]
    grupo_b = dezenas_ordenadas[chunk_size:chunk_size * 2]
    grupo_c = dezenas_ordenadas[chunk_size * 2:]

    grupos_utilizados = {
        'metodo': f'Estratégia Combinatória Balanceada ({total_pool} dezenas)',
        'total_jogos_solicitados': quantidade_jogos,
        'jogos_gerados': len(jogos_selecionados),
        'jogos_100_conformes': qtd_100_conformes,
        'grupos': {'A': grupo_a, 'B': grupo_b, 'C': grupo_c}
    }

    try:
        with engine_app.begin() as conn:
            conn.execute(
                text('UPDATE estrategia_fechamentos SET grupos = :grupos WHERE id = :id'),
                {'grupos': json.dumps(grupos_utilizados), 'id': fechamento_id}
            )
    except Exception as e:
        print("Erro ao atualizar metadados da estratégia:", str(e))

    return {
        'status': 'ok',
        'message': f'{len(jogos_selecionados)} jogos estratégicos gerados e salvos.',
        'detalhes': grupos_utilizados
    }
