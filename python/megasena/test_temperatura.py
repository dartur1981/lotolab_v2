"""
Script de Testes Unitários e Validação do Classificador de Temperatura da Mega-Sena.
"""

import unittest
import numpy as np
import pandas as pd
from megasena.classificador_temperatura import (
    calcular_temperatura_atual,
    calcular_temperaturas_historicas_sem_lookahead,
    obter_estatisticas_historicas_temperatura,
    validar_jogo_por_temperatura,
    JANELAS,
    PESOS,
    TAXA_ESPERADA
)


class TestClassificadorTemperatura(unittest.TestCase):

    def setUp(self):
        # 150 concursos sintéticos reproduzíveis
        np.random.seed(123)
        registros = []
        for c in range(1, 151):
            bolas = np.random.choice(np.arange(1, 61), size=6, replace=False)
            bolas.sort()
            registros.append({
                "concurso": c,
                "bola_1": int(bolas[0]),
                "bola_2": int(bolas[1]),
                "bola_3": int(bolas[2]),
                "bola_4": int(bolas[3]),
                "bola_5": int(bolas[4]),
                "bola_6": int(bolas[5])
            })
        self.df_sorteios = pd.DataFrame(registros)

    def test_parametros_fundamentais(self):
        self.assertEqual(JANELAS, [10, 20, 50, 100])
        self.assertEqual(PESOS, [0.40, 0.30, 0.20, 0.10])
        self.assertAlmostEqual(sum(PESOS), 1.0)
        self.assertAlmostEqual(TAXA_ESPERADA, 0.10)

    def test_temperatura_atual(self):
        df_atual = calcular_temperatura_atual(self.df_sorteios)
        self.assertEqual(len(df_atual), 60)
        self.assertTrue(set(df_atual["dezena"].values) == set(range(1, 61)))
        
        # Garante limites e categorias válidas
        for _, row in df_atual.iterrows():
            self.assertTrue(0.0 <= row["score_temperatura"] <= 100.0)
            self.assertTrue(0.0 <= row["r_10"] <= 100.0)
            self.assertTrue(0.0 <= row["r_20"] <= 100.0)
            self.assertTrue(0.0 <= row["r_50"] <= 100.0)
            self.assertTrue(0.0 <= row["r_100"] <= 100.0)

            if row["score_temperatura"] >= 55.0:
                self.assertEqual(row["categoria"], "quente")
            elif row["score_temperatura"] <= 35.0:
                self.assertEqual(row["categoria"], "frio")
            else:
                self.assertEqual(row["categoria"], "morno")

    def test_sem_lookahead_bias_e_soma_das_bolas(self):
        df_feat, df_scores = calcular_temperaturas_historicas_sem_lookahead(self.df_sorteios)
        self.assertEqual(len(df_feat), 150)
        
        # A soma de temp_quente + temp_morno + temp_frio DEVE ser rigorosamente 6 em todos os sorteios
        soma = df_feat["temp_quente"] + df_feat["temp_morno"] + df_feat["temp_frio"]
        self.assertTrue((soma == 6).all(), "A soma das temperaturas das 6 bolas deve ser 6.")

        # O primeiro sorteio (concurso 1) não possui histórico anterior
        # Portanto, o score de todas as dezenas no concurso 1 deve ser 0.0 (frio)
        self.assertEqual(df_feat.iloc[0]["temp_frio"], 6)
        self.assertEqual(df_feat.iloc[0]["temp_quente"], 0)
        self.assertEqual(df_feat.iloc[0]["temp_morno"], 0)

    def test_estatisticas_historicas(self):
        df_feat, _ = calcular_temperaturas_historicas_sem_lookahead(self.df_sorteios)
        stats = obter_estatisticas_historicas_temperatura(df_feat)
        self.assertEqual(stats["total_concursos_analisados"], 150)
        self.assertIn("medias_por_sorteio", stats)
        self.assertIn("perfis_mais_frequentes", stats)

    def test_validador_de_jogos(self):
        mapa_temp = {d: ("quente" if d <= 10 else ("morno" if d <= 40 else "frio")) for d in range(1, 61)}
        
        # Jogo balanceado: 2 quentes, 3 mornas, 1 fria -> deve ser válido
        jogo_balanceado = [1, 2, 15, 20, 25, 50]
        valido, contagem = validar_jogo_por_temperatura(jogo_balanceado, mapa_temp)
        self.assertTrue(valido)
        self.assertEqual(contagem["quente"], 2)
        self.assertEqual(contagem["morno"], 3)
        self.assertEqual(contagem["frio"], 1)

        # Jogo anômalo: 6 frias -> deve ser inválido
        jogo_frio = [51, 52, 53, 54, 55, 56]
        valido_frio, contagem_frio = validar_jogo_por_temperatura(jogo_frio, mapa_temp)
        self.assertFalse(valido_frio)
        self.assertEqual(contagem_frio["frio"], 6)


if __name__ == "__main__":
    unittest.main()
