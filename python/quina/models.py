import sys
import os

_parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from sqlalchemy import Column, Integer, Date, ForeignKey, String, Text, DateTime, Float
from quina.database import Base

class ResultadoQuina(Base):
    __tablename__ = "resultados_quina"

    concurso = Column(Integer, primary_key=True, index=True)
    data_sorteio = Column(Date, nullable=False)
    bola_1 = Column(Integer, nullable=False)
    bola_2 = Column(Integer, nullable=False)
    bola_3 = Column(Integer, nullable=False)
    bola_4 = Column(Integer, nullable=False)
    bola_5 = Column(Integer, nullable=False)

class FeaturesQuina(Base):
    __tablename__ = "features_quina"

    concurso = Column(Integer, ForeignKey("resultados_quina.concurso", ondelete="CASCADE"), primary_key=True)
    pares = Column(Integer, default=0)
    impares = Column(Integer, default=0)
    primos = Column(Integer, default=0)
    fibonacci = Column(Integer, default=0)
    soma = Column(Integer, default=0)

    # Métricas de Temperatura Histórica por Concurso (soma das 5 bolas)
    temp_quente = Column(Integer, default=0, nullable=False)
    temp_morno = Column(Integer, default=0, nullable=False)
    temp_frio = Column(Integer, default=0, nullable=False)

    # 4 Quadrantes Principais: Q1 (1-20), Q2 (21-40), Q3 (41-60), Q4 (61-80)
    quadrante_1 = Column(Integer, default=0)
    quadrante_2 = Column(Integer, default=0)
    quadrante_3 = Column(Integer, default=0)
    quadrante_4 = Column(Integer, default=0)

    # 8 Linhas (1 a 8)
    linha_1 = Column(Integer, default=0)
    linha_2 = Column(Integer, default=0)
    linha_3 = Column(Integer, default=0)
    linha_4 = Column(Integer, default=0)
    linha_5 = Column(Integer, default=0)
    linha_6 = Column(Integer, default=0)
    linha_7 = Column(Integer, default=0)
    linha_8 = Column(Integer, default=0)

    # 10 Colunas (1 a 10)
    coluna_1 = Column(Integer, default=0)
    coluna_2 = Column(Integer, default=0)
    coluna_3 = Column(Integer, default=0)
    coluna_4 = Column(Integer, default=0)
    coluna_5 = Column(Integer, default=0)
    coluna_6 = Column(Integer, default=0)
    coluna_7 = Column(Integer, default=0)
    coluna_8 = Column(Integer, default=0)
    coluna_9 = Column(Integer, default=0)
    coluna_10 = Column(Integer, default=0)

class ConfiguracaoAppQuina(Base):
    __tablename__ = "configuracoes_app_quina"
    
    chave = Column(String(50), primary_key=True)
    valor = Column(String(255), nullable=False)

class TendenciaDezenaQuina(Base):
    __tablename__ = "tendencias_dezenas_quina"
    
    dezena = Column(Integer, primary_key=True) # 1 a 80
    frequencia = Column(Integer, nullable=False, default=0)
    atraso = Column(Integer, nullable=False, default=0)
    score_temperatura = Column(Float, nullable=False, default=0.0)
    r_10 = Column(Float, nullable=False, default=0.0)
    r_20 = Column(Float, nullable=False, default=0.0)
    r_50 = Column(Float, nullable=False, default=0.0)
    r_100 = Column(Float, nullable=False, default=0.0)
    temperatura = Column(String(20), nullable=False, default="frio") # quente, morno, frio
    recomendacao = Column(String(50), nullable=True)
    score = Column(Integer, nullable=False, default=0)
