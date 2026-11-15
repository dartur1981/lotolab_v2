import sys
import os

_parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from sqlalchemy import Column, Integer, Date, ForeignKey, String, Text, DateTime, Float
from megasena.database import Base

class ResultadoMegasena(Base):
    __tablename__ = "resultados_megasena"

    concurso = Column(Integer, primary_key=True, index=True)
    data_sorteio = Column(Date, nullable=False)
    bola_1 = Column(Integer)
    bola_2 = Column(Integer)
    bola_3 = Column(Integer)
    bola_4 = Column(Integer)
    bola_5 = Column(Integer)
    bola_6 = Column(Integer)

class FeaturesMegasena(Base):
    __tablename__ = "features_megasena"

    concurso = Column(Integer, ForeignKey("resultados_megasena.concurso", ondelete="CASCADE"), primary_key=True)
    pares = Column(Integer)
    impares = Column(Integer)
    primos = Column(Integer)
    fibonacci = Column(Integer)
    soma = Column(Integer)
    
    # Métricas de Temperatura Histórica por Concurso (Sem Lookahead Bias, soma = 6)
    temp_quente = Column(Integer, default=0, nullable=False)
    temp_morno = Column(Integer, default=0, nullable=False)
    temp_frio = Column(Integer, default=0, nullable=False)
    
    # 3 Quadrantes Principais: Q1 (1-20), Q2 (21-40), Q3 (41-60)
    quadrante_1 = Column(Integer)
    quadrante_2 = Column(Integer)
    quadrante_3 = Column(Integer)

    # 15 Sub-Quadrantes (Blocos 2x2 de 4 números cada)
    sub_1 = Column(Integer)
    sub_2 = Column(Integer)
    sub_3 = Column(Integer)
    sub_4 = Column(Integer)
    sub_5 = Column(Integer)
    sub_6 = Column(Integer)
    sub_7 = Column(Integer)
    sub_8 = Column(Integer)
    sub_9 = Column(Integer)
    sub_10 = Column(Integer)
    sub_11 = Column(Integer)
    sub_12 = Column(Integer)
    sub_13 = Column(Integer)
    sub_14 = Column(Integer)
    sub_15 = Column(Integer)

    # 6 Linhas (1 a 6)
    linha_1 = Column(Integer)
    linha_2 = Column(Integer)
    linha_3 = Column(Integer)
    linha_4 = Column(Integer)
    linha_5 = Column(Integer)
    linha_6 = Column(Integer)

    # 10 Colunas (1 a 10)
    coluna_1 = Column(Integer)
    coluna_2 = Column(Integer)
    coluna_3 = Column(Integer)
    coluna_4 = Column(Integer)
    coluna_5 = Column(Integer)
    coluna_6 = Column(Integer)
    coluna_7 = Column(Integer)
    coluna_8 = Column(Integer)
    coluna_9 = Column(Integer)
    coluna_10 = Column(Integer)

class CicloMegasena(Base):
    __tablename__ = "ciclos_megasena"

    numero_ciclo = Column(Integer, primary_key=True, index=True)
    concurso_inicial = Column(Integer, nullable=False)
    concurso_final = Column(Integer, nullable=True)
    duracao = Column(Integer, nullable=False)
    status = Column(String(20), nullable=False) # "FECHADO" ou "ABERTO"
    json_matriz = Column(Text, nullable=True)

class ConfiguracaoAppMegasena(Base):
    __tablename__ = "configuracoes_app_megasena"
    
    chave = Column(String(50), primary_key=True)
    valor = Column(String(255), nullable=False)

class TendenciaDezenaMegasena(Base):
    __tablename__ = "tendencias_dezenas_megasena"
    
    dezena = Column(Integer, primary_key=True) # 1 a 60
    frequencia = Column(Integer, nullable=False)
    atraso = Column(Integer, nullable=False)
    score_temperatura = Column(Float, nullable=False, default=0.0)
    r_10 = Column(Float, nullable=False, default=0.0)
    r_20 = Column(Float, nullable=False, default=0.0)
    r_50 = Column(Float, nullable=False, default=0.0)
    r_100 = Column(Float, nullable=False, default=0.0)
    temperatura = Column(String(20), nullable=False) # quente, morno, frio
    status_ciclo = Column(String(20), nullable=False) # Sorteada, Pendente
    recomendacao = Column(String(50), nullable=False) # Alta Potência, Coringa, Baixa Potência
    score = Column(Integer, nullable=False)
