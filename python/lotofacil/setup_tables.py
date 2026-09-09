import sys
import os

_current_dir = os.path.dirname(os.path.abspath(__file__))
_parent_dir = os.path.dirname(_current_dir)
if _current_dir not in sys.path:
    sys.path.insert(0, _current_dir)
if _parent_dir not in sys.path:
    sys.path.insert(1, _parent_dir)

from lotofacil.database import (
    DB_HOST, DB_PORT, DB_USER, DB_NAME_ANALYTICS,
    engine, Base, init_databases
)
from lotofacil import models
from sqlalchemy import inspect

print("=" * 60)
print("INICIANDO SETUP DAS TABELAS ANALITICAS (PYTHON)")
print(f"Host: {DB_HOST}:{DB_PORT}")
print(f"Usuario: {DB_USER}")
print(f"Banco Analytics: {DB_NAME_ANALYTICS}")
print("=" * 60)

try:
    print("[1/3] Verificando / criando bancos de dados...")
    init_databases()
    print("      Bancos verificados com sucesso!")

    print("[2/3] Criando tabelas do modelo SQLAlchemy...")
    Base.metadata.create_all(bind=engine)
    print("      Tabelas criadas/sincronizadas com sucesso!")

    print("[3/3] Inspecionando tabelas existentes no banco:")
    inspector = inspect(engine)
    tables = inspector.get_table_names()
    for t in tables:
        print(f"      -> {t}")

    print("=" * 60)
    print(f"CONCLUIDO COM SUCESSO! Total de tabelas: {len(tables)}")
    print("=" * 60)

except Exception as e:
    print("=" * 60)
    print(f"ERRO CRITICO AO CRIAR TABELAS: {e}")
    print("=" * 60)
    import traceback
    traceback.print_exc()
    sys.exit(1)
