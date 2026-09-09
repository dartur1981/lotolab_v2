import os
import sys

_parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

import pandas as pd
from lotofacil.database import SessionLocal
from lotofacil import models
from collections import Counter

db = SessionLocal()
historico = db.query(models.ResultadoLotofacil).all()

# Moldura = {1, 2, 3, 4, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, 25}
MOLDURA = {1, 2, 3, 4, 5, 6, 10, 11, 15, 16, 20, 21, 22, 23, 24, 25}

def calc_linha(n): return (n - 1) // 5 + 1
def calc_coluna(n): return (n - 1) % 5 + 1

moldura_counts = []
linha_patterns = []
coluna_patterns = []

for r in historico:
    bolas = [r.bola_1, r.bola_2, r.bola_3, r.bola_4, r.bola_5, r.bola_6, r.bola_7, r.bola_8, r.bola_9, r.bola_10, r.bola_11, r.bola_12, r.bola_13, r.bola_14, r.bola_15]
    
    # Moldura
    qtd_moldura = sum(1 for b in bolas if b in MOLDURA)
    moldura_counts.append(qtd_moldura)
    
    # Linhas
    l_counts = {1:0, 2:0, 3:0, 4:0, 5:0}
    c_counts = {1:0, 2:0, 3:0, 4:0, 5:0}
    for b in bolas:
        l_counts[calc_linha(b)] += 1
        c_counts[calc_coluna(b)] += 1
        
    l_pattern = "-".join(map(str, sorted(l_counts.values(), reverse=True)))
    c_pattern = "-".join(map(str, sorted(c_counts.values(), reverse=True)))
    
    linha_patterns.append(l_pattern)
    coluna_patterns.append(c_pattern)
    
print("--- MOLDURA / MIOLO ---")
c_moldura = Counter(moldura_counts)
total = len(historico)
for qtd, freq in sorted(c_moldura.items()):
    pct = (freq / total) * 100
    print(f"{qtd} na moldura / {15-qtd} no miolo: {freq} vezes ({pct:.2f}%)")
    
print("\n--- PADROES DE LINHA ---")
c_linhas = Counter(linha_patterns)
for pat, freq in c_linhas.most_common(5):
    pct = (freq / total) * 100
    print(f"Padrão {pat}: {freq} vezes ({pct:.2f}%)")
    
print("\n--- PADROES DE COLUNA ---")
c_colunas = Counter(coluna_patterns)
for pat, freq in c_colunas.most_common(5):
    pct = (freq / total) * 100
    print(f"Padrão {pat}: {freq} vezes ({pct:.2f}%)")
