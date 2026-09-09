import json
import itertools
import pandas as pd
from datetime import datetime
from sqlalchemy.orm import Session
from sqlalchemy import text
from typing import List, Dict, Any

from lotofacil import models
from lotofacil.calculadora_combinacoes import PRIMOS, MOLDURA, FIBONACCI

# Constante de Múltiplos de 3
MULTIPLOS_TRES = {3, 6, 9, 12, 15, 18, 21, 24}

# Remover afinidades globais fixas
# Casamentos serão buscados do banco dinamicamente

def gerar_jogos_fechamento(db: Session, engine, engine_app, bolao_id: int, fechamento_id: int, quantidade_jogos: int, dezenas_pool: List[int]) -> Dict[str, Any]:
    """
    Motor matemático que gera combinações, aplica os filtros configurados no banco 
    e insere diretamente no banco de dados via Pandas/SQLAlchemy para máxima performance.
    """
    dezenas_pool = sorted(list(set(dezenas_pool)))
    total_pool = len(dezenas_pool)
    
    if total_pool < 15:
        return {"status": "error", "message": f"O pool possui apenas {total_pool} dezenas (mínimo 15)."}

    # Lê as configurações ativas do banco
    configs = {c.chave: c.valor for c in db.query(models.ConfiguracaoApp).all()}
    
    # Helpers para saber se os filtros estão ativos (1 = True, 0 = False)
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
        records = db.execute(text("SELECT dezena_1, dezena_2 FROM lotofacil_estatisticas_duplas ORDER BY frequencia DESC LIMIT 30")).fetchall()
        afinidades_fortes = [(r[0], r[1]) for r in records]
        
    jogos_validos = []
    
    if total_pool == 18:
        # Padrão: 3 grupos de 6, combinando 5 de cada (C(6,5) = 6 * 6 * 6 = 216 jogos potenciais)
        # 1. Ordenar dezenas para distribuição inteligente (Simulado aqui)
        ranking_quentes = {20: 1, 10: 2, 11: 3, 25: 4, 13: 5, 24: 6, 14: 7, 1: 8, 3: 9, 2: 10}
        dezenas_ordenadas = sorted(dezenas_pool, key=lambda x: ranking_quentes.get(x, 99))
        
        grupo_a = dezenas_ordenadas[0:6]
        grupo_b = dezenas_ordenadas[6:12]
        grupo_c = dezenas_ordenadas[12:18]
        
        combs_a = list(itertools.combinations(grupo_a, 5))
        combs_b = list(itertools.combinations(grupo_b, 5))
        combs_c = list(itertools.combinations(grupo_c, 5))
        
        todos_os_jogos = []
        for ca in combs_a:
            for cb in combs_b:
                for cc in combs_c:
                    jogo = sorted(list(ca) + list(cb) + list(cc))
                    todos_os_jogos.append(jogo)
                    
        # Aplica filtros em todos os jogos gerados
        jogos_filtrados = aplicar_filtros(
            todos_os_jogos, use_pares_impares, use_primos, use_moldura, 
            use_posicionais, use_casadas, use_fibonacci, use_multiplos_tres, 
            use_sequencias, use_soma, afinidades_fortes
        )
        
        # Pega a quantidade solicitada (ou todos se forem menos que a quantidade)
        # Já podemos randomizar ou usar uma ordem de score (as casadas teriam score maior)
        import random
        random.shuffle(jogos_filtrados)
        jogos_validos = jogos_filtrados[:quantidade_jogos]
        
        grupos_utilizados = {
            'metodo': 'Desdobramento em 3 Grupos de 6 (A, B, C) com Filtros Python',
            'total_jogos_solicitados': quantidade_jogos,
            'jogos_apos_filtros': len(jogos_validos),
            'grupos': {'A': grupo_a, 'B': grupo_b, 'C': grupo_c}
        }
        
    else:
        # Se diferente de 18, gera aleatoriamente com força bruta e aplica os filtros.
        grupos_utilizados = {
            'metodo': 'Aleatório Estratégico com Filtros Python',
            'total_jogos_solicitados': quantidade_jogos
        }
        
        tentativas = 0
        max_tentativas = quantidade_jogos * 50  # Limite maior pois os filtros podem reprovar muitos jogos
        
        import random
        hash_jogos = set()
        
        while len(jogos_validos) < quantidade_jogos and tentativas < max_tentativas:
            tentativas += 1
            pool_embaralhado = dezenas_pool.copy()
            random.shuffle(pool_embaralhado)
            jogo = sorted(pool_embaralhado[:15])
            
            h = tuple(jogo)
            if h in hash_jogos:
                continue
                
            if validar_jogo_unico(jogo, use_pares_impares, use_primos, use_moldura, use_posicionais, use_casadas, use_fibonacci, use_multiplos_tres, use_sequencias, use_soma, afinidades_fortes):
                jogos_validos.append(jogo)
                hash_jogos.add(h)
                
        grupos_utilizados['jogos_apos_filtros'] = len(jogos_validos)

    # ---------------------------------------------------------
    # Bulk Insert usando Pandas para lotofacil_fechamento_jogos
    # ---------------------------------------------------------
    if not jogos_validos:
        return {"status": "error", "message": "Nenhum jogo passou nos filtros rigorosos. Desative alguns filtros ou forneça mais dezenas."}
        
    agora = datetime.now()
    records = []
    for jogo in jogos_validos:
        records.append({
            "fechamento_id": fechamento_id,
            "dezenas": json.dumps(jogo),
            "status": "0",  # Pendente
            "created_at": agora,
            "updated_at": agora
        })
        
    df = pd.DataFrame(records)
    
    # Salva direto na tabela do Laravel usando engine_app (lotolab_app_v2)
    df.to_sql(
        name='lotofacil_fechamento_jogos', 
        con=engine_app, 
        if_exists='append', 
        index=False, 
        chunksize=2000, 
        method='multi'
    )
    
    # Atualizar metadados do fechamento (opcional, atualizar a coluna grupos via SQLAlchemy)
    try:
        with engine_app.begin() as conn:
            conn.execute(
                text("UPDATE lotofacil_fechamentos SET grupos = :grupos WHERE id = :id"),
                {"grupos": json.dumps(grupos_utilizados), "id": fechamento_id}
            )
            
            # Atualiza o valor do bolão
            # Cada jogo de 15 dz custa 3.00 (ajustar depois conforme ConfiguracaoApp)
            valor_aposta = float(configs.get('valor_aposta', '3.00'))
            valor_total = len(jogos_validos) * valor_aposta
            
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
        pass

    return {
        "status": "ok",
        "message": f"{len(jogos_validos)} jogos gerados e salvos com sucesso.",
        "detalhes": grupos_utilizados
    }

def aplicar_filtros(jogos: List[List[int]], use_pares, use_primos, use_moldura, use_posicionais, use_casadas, use_fibonacci, use_multiplos_tres, use_sequencias, use_soma, afinidades_fortes) -> List[List[int]]:
    aprovados = []
    for j in jogos:
        if validar_jogo_unico(j, use_pares, use_primos, use_moldura, use_posicionais, use_casadas, use_fibonacci, use_multiplos_tres, use_sequencias, use_soma, afinidades_fortes):
            aprovados.append(j)
    return aprovados

def validar_jogo_unico(jogo: List[int], use_pares: bool, use_primos: bool, use_moldura: bool, use_posicionais: bool, use_casadas: bool, use_fibonacci: bool, use_multiplos_tres: bool, use_sequencias: bool, use_soma: bool, afinidades_fortes: List[tuple]) -> bool:
    if use_pares:
        pares = sum(1 for n in jogo if n % 2 == 0)
        impares = 15 - pares
        # Padrão mais comum: 7 pares / 8 ímpares ou 8 pares / 7 ímpares
        if not ((pares == 7 and impares == 8) or (pares == 8 and impares == 7)):
            return False
            
    if use_primos:
        primos = sum(1 for n in jogo if n in PRIMOS)
        # Padrão mais comum: 4 a 6 primos
        if primos < 4 or primos > 6:
            return False
            
    if use_moldura:
        moldura = sum(1 for n in jogo if n in MOLDURA)
        # Padrão mais comum: 9 a 10 dezenas na moldura
        if moldura < 9 or moldura > 10:
            return False
            
    if use_posicionais:
        # Posição 1 costuma ser 1, 2 ou 3
        if jogo[0] > 3:
            return False
        # Posição 15 costuma ser 23, 24 ou 25
        if jogo[-1] < 23:
            return False
            
    if use_fibonacci:
        fibonacci_count = sum(1 for n in jogo if n in FIBONACCI)
        if fibonacci_count < 3 or fibonacci_count > 5:
            return False
            
    if use_multiplos_tres:
        mult_tres_count = sum(1 for n in jogo if n in MULTIPLOS_TRES)
        if mult_tres_count < 4 or mult_tres_count > 6:
            return False
            
    if use_sequencias:
        max_seq = 1
        current_seq = 1
        for i in range(1, len(jogo)):
            if jogo[i] == jogo[i-1] + 1:
                current_seq += 1
                if current_seq > max_seq:
                    max_seq = current_seq
            else:
                current_seq = 1
        
        # Descarta jogos com mais de 7 números em sequência ininterrupta
        if max_seq > 7:
            return False
            
    if use_soma:
        soma_total = sum(jogo)
        if soma_total < 180 or soma_total > 210:
            return False
            
    if use_casadas and afinidades_fortes:
        tem_afinidade = False
        for d1, d2 in afinidades_fortes:
            if d1 in jogo and d2 in jogo:
                tem_afinidade = True
                break
        if not tem_afinidade:
            return False
            
    return True
def pontuar_jogo(jogo, afinidades_fortes):
    score = 0
    pares = sum(1 for n in jogo if n % 2 == 0)
    if pares in (7, 8): score += 10
    primos = sum(1 for n in jogo if n in PRIMOS)
    if primos in (4, 5, 6): score += 10
    moldura = sum(1 for n in jogo if n in MOLDURA)
    if moldura in (9, 10): score += 10
    if afinidades_fortes:
        for d1, d2 in afinidades_fortes:
            if d1 in jogo and d2 in jogo: score += 20
    return score

def gerar_jogos_estrategia(db, engine_app, fechamento_id: int, quantidade_jogos: int, dezenas_pool: list):
    configs = {}
    afinidades_fortes = [(1,2), (24,25), (3,4), (13,14)]

    if len(dezenas_pool) < 15:
        return {'status': 'error', 'message': 'O pool possui menos de 15 dezenas.'}

    dezenas_ordenadas = sorted(list(set(dezenas_pool)))

    if len(dezenas_ordenadas) == 18:
        import itertools
        todos_os_jogos = list(itertools.combinations(dezenas_ordenadas, 15))
        todos_os_jogos = [list(j) for j in todos_os_jogos]
        
        jogos_com_score = [(j, pontuar_jogo(j, afinidades_fortes)) for j in todos_os_jogos]
        jogos_com_score.sort(key=lambda x: x[1], reverse=True)
        
        jogos_validos = jogos_com_score[:quantidade_jogos]
        grupos_utilizados = {
            'metodo': 'Todas as Combinacoes (816) ranqueadas por Score',
            'total_jogos_solicitados': quantidade_jogos,
            'jogos_gerados': len(jogos_validos)
        }
    else:
        import random
        tentativas = 0
        max_tentativas = quantidade_jogos * 100
        hash_jogos = set()
        jogos_encontrados = []
        
        while len(jogos_encontrados) < quantidade_jogos * 2 and tentativas < max_tentativas:
            tentativas += 1
            pool_embaralhado = dezenas_pool.copy()
            random.shuffle(pool_embaralhado)
            jogo = sorted(pool_embaralhado[:15])
            h = tuple(jogo)
            if h in hash_jogos: continue
            
            # Aqui podemos relaxar ou não os filtros
            if validar_jogo_unico(jogo, True, True, True, True, True, True, True, True, True, afinidades_fortes):
                jogos_encontrados.append(jogo)
                hash_jogos.add(h)
                
        jogos_com_score = [(j, pontuar_jogo(j, afinidades_fortes)) for j in jogos_encontrados]
        jogos_com_score.sort(key=lambda x: x[1], reverse=True)
        jogos_validos = jogos_com_score[:quantidade_jogos]
        grupos_utilizados = {
            'metodo': 'Aleatorio Estrategico',
            'total_jogos_solicitados': quantidade_jogos,
            'jogos_apos_filtros': len(jogos_encontrados)
        }

    if not jogos_validos:
        return {'status': 'error', 'message': 'Nenhum jogo gerado.'}
        
    import pandas as pd
    import json
    from datetime import datetime
    from sqlalchemy import text
    agora = datetime.now()
    records = []
    for jogo_tupla in jogos_validos:
        jogo = jogo_tupla[0]
        score = jogo_tupla[1]
        records.append({
            'estrategia_fechamento_id': fechamento_id,
            'dezenas': json.dumps(jogo),
            'score': score,
            'status': '0',
            'created_at': agora,
            'updated_at': agora
        })
        
    df = pd.DataFrame(records)
    df.to_sql('estrategia_fechamento_jogos', engine_app, if_exists='append', index=False, chunksize=2000, method='multi')
    
    try:
        with engine_app.begin() as conn:
            conn.execute(
                text('UPDATE estrategia_fechamentos SET grupos = :grupos WHERE id = :id'),
                {'grupos': json.dumps(grupos_utilizados), 'id': fechamento_id}
            )
    except Exception:
        pass

    return {'status': 'ok', 'message': f'{len(jogos_validos)} jogos estrategicos gerados e salvos.'}
