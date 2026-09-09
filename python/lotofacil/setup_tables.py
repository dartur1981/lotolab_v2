import sys
import os

_current_dir = os.path.dirname(os.path.abspath(__file__))
_parent_dir = os.path.dirname(_current_dir)
if _current_dir not in sys.path:
    sys.path.insert(0, _current_dir)
if _parent_dir not in sys.path:
    sys.path.insert(1, _parent_dir)

try:
    from lotofacil.database import (
        DB_HOST, DB_PORT, DB_USER, DB_NAME_ANALYTICS, DB_NAME_APP,
        engine, Base, init_databases
    )
    from lotofacil import models
    from sqlalchemy import inspect
except Exception as err:
    print("=" * 60)
    print("ERRO DE CONFIGURAÇÃO DE BANCO:")
    print(err)
    print("=" * 60)
    sys.exit(1)

print("=" * 60)
print("SETUP DE TABELAS ANALÍTICAS (PYTHON)")
print(f"Host: {DB_HOST}:{DB_PORT}")
print(f"Usuário: {DB_USER}")
print(f"Banco Analítico: {DB_NAME_ANALYTICS}")
print(f"Banco da Aplicação: {DB_NAME_APP}")
print("=" * 60)

try:
    print("[1/3] Garantindo existência dos bancos de dados no MySQL...")
    init_databases()
    print("      Bancos verificados/criados com sucesso!")

    print("[2/3] Criando/sincronizando tabelas do modelo SQLAlchemy...")
    Base.metadata.create_all(bind=engine)
    print("      Tabelas criadas com sucesso!")

    print("[3/3] Tabelas ativas no banco analítico:")
    inspector = inspect(engine)
    tables = inspector.get_table_names()
    for t in tables:
        print(f"      -> {t}")

    print("=" * 60)
    print(f"SUCESSO! Total de tabelas analíticas: {len(tables)}")
    print("=" * 60)

except Exception as e:
    print("=" * 60)
    print(f"FALHA AO CONECTAR OU CRIAR TABELAS: {e}")
    print("=" * 60)
    import traceback
    traceback.print_exc()
    sys.exit(1)
