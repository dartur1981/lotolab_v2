import sys
import os

_parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from sqlalchemy import Column, Integer, Date, ForeignKey, String, Text, DateTime
from lotofacil.database import Base

class ResultadoLotofacil(Base):
    __tablename__ = "resultados_lotofacil"

    concurso = Column(Integer, primary_key=True, index=True)
    data_sorteio = Column(Date, nullable=False)
    bola_1 = Column(Integer)
    bola_2 = Column(Integer)
    bola_3 = Column(Integer)
    bola_4 = Column(Integer)
    bola_5 = Column(Integer)
    bola_6 = Column(Integer)
    bola_7 = Column(Integer)
    bola_8 = Column(Integer)
    bola_9 = Column(Integer)
    bola_10 = Column(Integer)
    bola_11 = Column(Integer)
    bola_12 = Column(Integer)
    bola_13 = Column(Integer)
    bola_14 = Column(Integer)
    bola_15 = Column(Integer)

class FeaturesLotofacil(Base):
    __tablename__ = "features_lotofacil"

    concurso = Column(Integer, ForeignKey("resultados_lotofacil.concurso"), primary_key=True)
    pares = Column(Integer)
    impares = Column(Integer)
    primos = Column(Integer)
    fibonacci = Column(Integer)
    moldura = Column(Integer)
    miolo = Column(Integer)
    soma = Column(Integer)
    
    # Linhas
    linha_1 = Column(Integer)
    linha_2 = Column(Integer)
    linha_3 = Column(Integer)
    linha_4 = Column(Integer)
    linha_5 = Column(Integer)
    
    # Colunas
    coluna_1 = Column(Integer)
    coluna_2 = Column(Integer)
    coluna_3 = Column(Integer)
    coluna_4 = Column(Integer)
    coluna_5 = Column(Integer)

class CicloLotofacil(Base):
    __tablename__ = "ciclos_lotofacil"

    numero_ciclo = Column(Integer, primary_key=True, index=True)
    concurso_inicial = Column(Integer, nullable=False)
    concurso_final = Column(Integer, nullable=True)
    duracao = Column(Integer, nullable=False)
    status = Column(String(20), nullable=False) # "FECHADO" ou "ABERTO"
    json_matriz = Column(Text, nullable=True)

class Combinacao18(Base):
    __tablename__ = "combinacoes_18"

    id = Column(Integer, primary_key=True, autoincrement=True)
    dezenas = Column(String(100), unique=True, index=True) # Ex: "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18"
    pares = Column(Integer)
    impares = Column(Integer)
    primos = Column(Integer)
    fibonacci = Column(Integer)
    moldura = Column(Integer)
    miolo = Column(Integer)
    soma = Column(Integer)
    
    # Contadores de performance
    acertos_15 = Column(Integer, default=0)
    acertos_14 = Column(Integer, default=0)
    acertos_13 = Column(Integer, default=0)
    acertos_12 = Column(Integer, default=0)
    acertos_11 = Column(Integer, default=0)

    # Score de qualidade e repetições com últimos concursos
    score = Column(Integer, nullable=True)
    repetidas_ant1 = Column(Integer, nullable=True)
    repetidas_ant2 = Column(Integer, nullable=True)
    repetidas_ant3 = Column(Integer, nullable=True)

    # Performance histórica acumulada em todos os concursos reais
    historico_15 = Column(Integer, default=0)
    historico_14 = Column(Integer, default=0)
    historico_13 = Column(Integer, default=0)
    historico_12 = Column(Integer, default=0)
    historico_11 = Column(Integer, default=0)

class ConfiguracaoApp(Base):
    __tablename__ = "configuracoes_app"
    
    chave = Column(String(50), primary_key=True)
    valor = Column(String(255), nullable=False)

class TendenciaDezena(Base):
    __tablename__ = "tendencias_dezenas"
    
    dezena = Column(Integer, primary_key=True) # 1 a 25
    frequencia = Column(Integer, nullable=False)
    atraso = Column(Integer, nullable=False)
    temperatura = Column(String(20), nullable=False) # Quente, Morna, Fria
    status_ciclo = Column(String(20), nullable=False) # Sorteada, Pendente
    recomendacao = Column(String(50), nullable=False) # Alta Potência, Coringa, Baixa Potência
    score = Column(Integer, nullable=False) # Pontuação para ordenação

class EstatisticaDupla(Base):
    __tablename__ = "lotofacil_estatisticas_duplas"
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    dezena_1 = Column(Integer, nullable=False, index=True)
    dezena_2 = Column(Integer, nullable=False, index=True)
    frequencia = Column(Integer, default=0)
    percentual = Column(Integer, default=0)
    created_at = Column(DateTime)
    updated_at = Column(DateTime)

class EstatisticaTrinca(Base):
    __tablename__ = "lotofacil_estatisticas_trincas"
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    dezenas = Column(String(10), unique=True, nullable=False)
    frequencia = Column(Integer, default=0)
    percentual = Column(Integer, default=0) # Armazenaremos como decimal nativo ou int dependendo do backend, mas SQLAlchemy lida bem
    created_at = Column(DateTime)
    updated_at = Column(DateTime)

class EstatisticaPadrao(Base):
    __tablename__ = "lotofacil_estatisticas_padroes"
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    padrao = Column(String(50), unique=True, nullable=False)
    valor = Column(Integer, default=0)
    atraso = Column(Integer, default=0)
    percentual = Column(Integer, default=0)
    created_at = Column(DateTime)
    updated_at = Column(DateTime)

