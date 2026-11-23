"""
Módulo de Classificação de Temperatura das Dezenas da Quina.
Rigorosamente aderente às especificações matemáticas:
- Universo: 80 dezenas (1 a 80), 5 bolas por sorteio.
- Taxa Teórica Esperada: 5 / 80 = 0.0625 (6.25%).
- 4 Janelas Móveis Ponderadas:
  * Janela 1: 10 concursos  -> Peso = 0.40 (40%)
  * Janela 2: 20 concursos  -> Peso = 0.30 (30%)
  * Janela 3: 50 concursos  -> Peso = 0.20 (20%)
  * Janela 4: 100 concursos -> Peso = 0.10 (10%)
- Razão indexada: r_N = min(100.0, (taxa_obs / 0.0625) * 100)
- Score final: (0.40 * r_10) + (0.30 * r_20) + (0.20 * r_50) + (0.10 * r_100)
- Classificação:
  * "quente": score >= 55.0
  * "frio"  : score <= 35.0
  * "morno" : 35.0 < score < 55.0
- Processamento histórico livre de lookahead bias (shift 1 retroativo).
"""

from typing import Tuple, Dict, Any, List, Optional
import numpy as np
import pandas as pd

JANELAS = [10, 20, 50, 100]
PESOS = [0.40, 0.30, 0.20, 0.10]
TAXA_ESPERADA = 5.0 / 80.0  # 0.0625 (6.25%)
LIMIAR_QUENTE = 55.0
LIMIAR_FRIO = 35.0

def categorizar_temperatura(score: float) -> str:
    """
    Classifica qualitativamente o score de temperatura:
    - 'quente': score >= 55.0
    - 'frio'  : score <= 35.0
    - 'morno' : 35.0 < score < 55.0
    """
    if score >= LIMIAR_QUENTE:
        return "quente"
    elif score <= LIMIAR_FRIO:
        return "frio"
    return "morno"

def extrair_matriz_binaria(df_sorteios: pd.DataFrame, colunas_bolas: Optional[List[str]] = None) -> Tuple[np.ndarray, np.ndarray]:
    """
    Converte DataFrame de sorteios ordenados por concurso em uma matriz binária (T, 80).
    Retorna (matriz_binaria, concursos).
    """
    if colunas_bolas is None:
        colunas_bolas = ["bola_1", "bola_2", "bola_3", "bola_4", "bola_5"]

    df_ord = df_sorteios.sort_values("concurso", ascending=True).copy()
    concursos = df_ord["concurso"].values
    n_concursos = len(df_ord)

    matriz_binaria = np.zeros((n_concursos, 80), dtype=np.float64)
    bolas_matriz = df_ord[colunas_bolas].values

    for col in range(5):
        b = bolas_matriz[:, col]
        validos = (b >= 1) & (b <= 80)
        np.add.at(matriz_binaria, (np.where(validos)[0], b[validos] - 1), 1.0)

    return matriz_binaria, concursos

def calcular_temperatura_atual(df_sorteios: pd.DataFrame) -> pd.DataFrame:
    """
    Calcula a temperatura atual (do concurso mais recente para trás)
    para todas as 80 dezenas da Quina.

    Retorna DataFrame com:
    ['dezena', 'r_10', 'r_20', 'r_50', 'r_100', 'score_temperatura', 'categoria']
    """
    if df_sorteios.empty:
        raise ValueError("DataFrame de sorteios está vazio.")

    colunas_bolas = ["bola_1", "bola_2", "bola_3", "bola_4", "bola_5"]
    matriz_binaria, _ = extrair_matriz_binaria(df_sorteios, colunas_bolas)
    total_concursos = matriz_binaria.shape[0]

    r_scores = {}
    score_final = np.zeros(80, dtype=np.float64)

    for N, peso in zip(JANELAS, PESOS):
        janela_efetiva = min(total_concursos, N)
        if janela_efetiva > 0:
            freq = matriz_binaria[-janela_efetiva:, :].sum(axis=0)
            taxa_obs = freq / float(janela_efetiva)
        else:
            taxa_obs = np.zeros(80)

        r_N = np.minimum(100.0, (taxa_obs / TAXA_ESPERADA) * 100.0)
        r_scores[f"r_{N}"] = r_N
        score_final += peso * r_N

    resultados = []
    for d in range(80):
        dezena_num = d + 1
        sc = float(np.round(score_final[d], 4))
        resultados.append({
            "dezena": dezena_num,
            "r_10": float(np.round(r_scores["r_10"][d], 4)),
            "r_20": float(np.round(r_scores["r_20"][d], 4)),
            "r_50": float(np.round(r_scores["r_50"][d], 4)),
            "r_100": float(np.round(r_scores["r_100"][d], 4)),
            "score_temperatura": sc,
            "categoria": categorizar_temperatura(sc)
        })

    return pd.DataFrame(resultados)

def calcular_temperaturas_historicas_sem_lookahead(df_sorteios: pd.DataFrame) -> Tuple[pd.DataFrame, np.ndarray]:
    """
    Calcula matriz (T, 80) de temperaturas históricas retroativas sem lookahead bias.
    Para o concurso t, as taxas observadas utilizam apenas sorteios até t-1.
    Retorna (df_metricas_features, matriz_scores).
    """
    colunas_bolas = ["bola_1", "bola_2", "bola_3", "bola_4", "bola_5"]
    matriz_binaria, concursos = extrair_matriz_binaria(df_sorteios, colunas_bolas)
    T, D = matriz_binaria.shape

    matriz_cum = np.vstack([np.zeros((1, D)), np.cumsum(matriz_binaria, axis=0)])

    scores_t = np.zeros((T, D), dtype=np.float64)

    for t in range(T):
        if t == 0:
            scores_t[t, :] = 50.0  # Neutro antes do primeiro concurso
            continue

        score_deg = np.zeros(D, dtype=np.float64)
        for N, peso in zip(JANELAS, PESOS):
            janela_efetiva = min(t, N)
            idx_inicio = t - janela_efetiva
            freq = matriz_cum[t, :] - matriz_cum[idx_inicio, :]
            taxa_obs = freq / float(janela_efetiva)
            r_N = np.minimum(100.0, (taxa_obs / TAXA_ESPERADA) * 100.0)
            score_deg += peso * r_N

        scores_t[t, :] = score_deg

    df_ord = df_sorteios.sort_values("concurso", ascending=True).copy()
    bolas_matriz = df_ord[colunas_bolas].values

    registros_feat = []
    for t in range(T):
        conc = int(concursos[t])
        dezenas_sorteadas = bolas_matriz[t, :]
        validos = (dezenas_sorteadas >= 1) & (dezenas_sorteadas <= 80)
        idx_sorteados = dezenas_sorteadas[validos].astype(int) - 1

        scores_sorteados = scores_t[t, idx_sorteados]
        n_quente = int(np.sum(scores_sorteados >= LIMIAR_QUENTE))
        n_frio = int(np.sum(scores_sorteados <= LIMIAR_FRIO))
        n_morno = int(len(scores_sorteados) - n_quente - n_frio)

        registros_feat.append({
            "concurso": conc,
            "temp_quente": n_quente,
            "temp_morno": n_morno,
            "temp_frio": n_frio
        })

    return pd.DataFrame(registros_feat), scores_t
