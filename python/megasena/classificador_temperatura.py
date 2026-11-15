"""
Módulo de Classificação de Temperatura das Dezenas da Mega-Sena.
Rigorosamente aderente às especificações matemáticas:
- Universo: 60 dezenas (1 a 60), 6 bolas por sorteio.
- Taxa Teórica Esperada: 6 / 60 = 0.10 (10%).
- 4 Janelas Móveis Ponderadas:
  * Janela 1: 10 concursos  -> Peso = 0.40 (40%)
  * Janela 2: 20 concursos  -> Peso = 0.30 (30%)
  * Janela 3: 50 concursos  -> Peso = 0.20 (20%)
  * Janela 4: 100 concursos -> Peso = 0.10 (10%)
- Razão indexada: r_N = min(100.0, (taxa_obs / 0.10) * 100)
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

# Parâmetros e Constantes do Negócio
JANELAS = [10, 20, 50, 100]
PESOS = [0.40, 0.30, 0.20, 0.10]
TAXA_ESPERADA = 6.0 / 60.0  # 0.10 (10%)
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
    Converte DataFrame de sorteios ordenados por concurso em uma matriz binária (T, 60).
    Retorna (matriz_binaria, concursos).
    """
    if colunas_bolas is None:
        colunas_bolas = ["bola_1", "bola_2", "bola_3", "bola_4", "bola_5", "bola_6"]

    df_ord = df_sorteios.sort_values("concurso", ascending=True).copy()
    concursos = df_ord["concurso"].values
    n_concursos = len(df_ord)

    matriz_binaria = np.zeros((n_concursos, 60), dtype=np.float64)
    bolas_matriz = df_ord[colunas_bolas].values

    for col in range(6):
        b = bolas_matriz[:, col]
        validos = (b >= 1) & (b <= 60)
        np.add.at(matriz_binaria, (np.where(validos)[0], b[validos] - 1), 1.0)

    return matriz_binaria, concursos


def calcular_temperatura_atual(df_sorteios: pd.DataFrame) -> pd.DataFrame:
    """
    Calcula a temperatura atual (do concurso mais recente para trás)
    para todas as 60 dezenas da Mega-Sena.

    Retorna DataFrame com:
    ['dezena', 'r_10', 'r_20', 'r_50', 'r_100', 'score_temperatura', 'categoria']
    """
    if df_sorteios.empty:
        raise ValueError("DataFrame de sorteios está vazio.")

    colunas_bolas = ["bola_1", "bola_2", "bola_3", "bola_4", "bola_5", "bola_6"]
    matriz_binaria, _ = extrair_matriz_binaria(df_sorteios, colunas_bolas)
    total_concursos = matriz_binaria.shape[0]

    r_scores = {}
    score_final = np.zeros(60, dtype=np.float64)

    for N, peso in zip(JANELAS, PESOS):
        janela_efetiva = min(total_concursos, N)
        if janela_efetiva > 0:
            freq = matriz_binaria[-janela_efetiva:, :].sum(axis=0)
            taxa_obs = freq / float(janela_efetiva)
        else:
            taxa_obs = np.zeros(60)

        # r_N = min(100.0, (taxa_obs / 0.10) * 100)
        r_N = np.minimum(100.0, (taxa_obs / TAXA_ESPERADA) * 100.0)
        r_scores[f"r_{N}"] = r_N
        score_final += peso * r_N

    resultados = []
    for d in range(1, 61):
        idx = d - 1
        s = float(score_final[idx])
        resultados.append({
            "dezena": d,
            "r_10": round(float(r_scores["r_10"][idx]), 2),
            "r_20": round(float(r_scores["r_20"][idx]), 2),
            "r_50": round(float(r_scores["r_50"][idx]), 2),
            "r_100": round(float(r_scores["r_100"][idx]), 2),
            "score_temperatura": round(s, 2),
            "categoria": categorizar_temperatura(s)
        })

    return pd.DataFrame(resultados).sort_values("score_temperatura", ascending=False).reset_index(drop=True)


def calcular_temperaturas_historicas_sem_lookahead(
    df_sorteios: pd.DataFrame
) -> Tuple[pd.DataFrame, pd.DataFrame]:
    """
    Processa todos os concursos da base histórica sem vazamento de dados futuros (Lookahead Bias).
    Para cada sorteio i (onde i é 0-indexed no array cronológico):
      - A temperatura de cada dezena é calculada estritamente com os sorteios [0 até i-1].
      - No concurso i, avalia as 6 dezenas sorteadas de acordo com a temperatura que tinham
        no momento pré-sorteio.
      - Contabiliza temp_quente, temp_morno e temp_frio (a soma é rigorosamente 6).

    Retorna:
    1. df_features_temperatura: DataFrame indexado por concurso com:
       ['concurso', 'temp_quente', 'temp_morno', 'temp_frio']
    2. df_scores_historicos: Matriz completa de scores (linhas = concursos, colunas = D_01..D_60)
    """
    df_ord = df_sorteios.sort_values("concurso", ascending=True).reset_index(drop=True)
    n_concursos = len(df_ord)
    concursos = df_ord["concurso"].values

    colunas_bolas = ["bola_1", "bola_2", "bola_3", "bola_4", "bola_5", "bola_6"]
    bolas_matriz = df_ord[colunas_bolas].values

    matriz_binaria, _ = extrair_matriz_binaria(df_ord, colunas_bolas)

    # DataFrame pandas para rolling sum
    df_bin = pd.DataFrame(matriz_binaria)
    scores_historicos = np.zeros((n_concursos, 60), dtype=np.float64)

    # Vetor de concursos passados decorridos antes de cada linha i (0, 1, 2, ..., n-1)
    concursos_passados = np.arange(n_concursos)[:, None]  # shape: (n, 1)

    for N, peso in zip(JANELAS, PESOS):
        # shift(1) garante que o concurso atual 'i' NÃO vê o seu próprio sorteio
        # min_periods=1 permite calcular para os primeiros concursos históricos
        freq_passada = df_bin.rolling(window=N, min_periods=1).sum().shift(1).fillna(0.0).values
        
        # O divisor é a quantidade real de concursos passados disponíveis (no máximo N)
        divisor = np.clip(concursos_passados, 1, N)

        # Taxa observada retroativa
        taxa_obs = freq_passada / divisor
        
        # Razão clampeada de 0 a 100
        r_N = np.minimum(100.0, (taxa_obs / TAXA_ESPERADA) * 100.0)

        # Para o concurso 0 (primeiro concurso da história), como não há sorteios passados,
        # freq_passada = 0 e r_N = 0.
        scores_historicos += peso * r_N

    # Categorias históricas: 2 = quente, 1 = morno, 0 = frio
    categorias_matriz = np.ones((n_concursos, 60), dtype=np.int8)  # default morno
    categorias_matriz[scores_historicos >= LIMIAR_QUENTE] = 2       # quente
    categorias_matriz[scores_historicos <= LIMIAR_FRIO] = 0         # frio

    # Contabilização para cada concurso
    temp_quente = np.zeros(n_concursos, dtype=np.int32)
    temp_morno = np.zeros(n_concursos, dtype=np.int32)
    temp_frio = np.zeros(n_concursos, dtype=np.int32)

    for i in range(n_concursos):
        bolas = bolas_matriz[i]
        # Índices 0 a 59
        indices_bolas = [int(b) - 1 for b in bolas if 1 <= b <= 60]
        
        cats = categorias_matriz[i, indices_bolas]
        temp_quente[i] = int(np.sum(cats == 2))
        temp_morno[i] = int(np.sum(cats == 1))
        temp_frio[i] = int(np.sum(cats == 0))

    df_features_temperatura = pd.DataFrame({
        "concurso": concursos,
        "temp_quente": temp_quente,
        "temp_morno": temp_morno,
        "temp_frio": temp_frio
    })

    df_scores_historicos = pd.DataFrame(
        scores_historicos,
        index=concursos,
        columns=[f"D_{d:02d}" for d in range(1, 61)]
    )

    return df_features_temperatura, df_scores_historicos


def obter_estatisticas_historicas_temperatura(df_features_temperatura: pd.DataFrame) -> Dict[str, Any]:
    """
    Calcula médias históricas e distribuições dos perfis de temperatura (Q-M-F).
    """
    if df_features_temperatura.empty:
        return {}

    total_jogos = len(df_features_temperatura)
    media_quente = float(df_features_temperatura["temp_quente"].mean())
    media_morno = float(df_features_temperatura["temp_morno"].mean())
    media_frio = float(df_features_temperatura["temp_frio"].mean())

    # Distribuição dos perfis combinados (ex: '2Q-3M-1F')
    perfis = (
        df_features_temperatura["temp_quente"].astype(str) + "Q-" +
        df_features_temperatura["temp_morno"].astype(str) + "M-" +
        df_features_temperatura["temp_frio"].astype(str) + "F"
    )
    contagem_perfis = perfis.value_counts()
    distribuicao_perfis = [
        {
            "perfil": str(perfil),
            "ocorrencias": int(qtd),
            "percentual": round(float(qtd / total_jogos) * 100, 2)
        }
        for perfil, qtd in contagem_perfis.head(10).items()
    ]

    return {
        "total_concursos_analisados": total_jogos,
        "medias_por_sorteio": {
            "temp_quente": round(media_quente, 2),
            "temp_morno": round(media_morno, 2),
            "temp_frio": round(media_frio, 2)
        },
        "perfis_mais_frequentes": distribuicao_perfis
    }


def validar_jogo_por_temperatura(
    dezenas: List[int],
    mapa_temperaturas: Dict[int, str],
    min_quentes: int = 1,
    max_quentes: int = 4,
    min_mornas: int = 1,
    max_mornas: int = 4,
    max_frias: int = 2
) -> Tuple[bool, Dict[str, int]]:
    """
    Valida se um jogo específico de dezenas obedece aos limites de temperatura.
    """
    contagem = {"quente": 0, "morno": 0, "frio": 0}
    for d in dezenas:
        cat = mapa_temperaturas.get(d, "morno")
        contagem[cat] += 1

    valido = (
        (min_quentes <= contagem["quente"] <= max_quentes) and
        (min_mornas <= contagem["morno"] <= max_mornas) and
        (contagem["frio"] <= max_frias)
    )
    return valido, contagem
