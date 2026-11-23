import os
from sqlalchemy import create_engine
from sqlalchemy.orm import declarative_base, sessionmaker

def load_dotenv_fallback():
    """Carrega web/.env caso as variáveis ainda não estejam no ambiente do SO"""
    base_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
    candidatos = [
        os.path.join(os.getcwd(), "web", ".env"),
        os.path.join(base_dir, "web", ".env"),
        os.path.join(base_dir, ".env"),
    ]
    for env_path in candidatos:
        if os.path.isfile(env_path):
            with open(env_path, "r", encoding="utf-8") as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith("#") and "=" in line:
                        k, v = line.split("=", 1)
                        k = k.strip()
                        v = v.strip().strip('"').strip("'")
                        if k not in os.environ:
                            os.environ[k] = v
            break

load_dotenv_fallback()

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = os.getenv("DB_PORT", "3306")
DB_USER = os.getenv("DB_USERNAME", "developer")
DB_PASS = os.getenv("DB_PASSWORD", "d3v3l0p3r")
DB_NAME_ANALYTICS = os.getenv("DB_DATABASE_QUINA_ANALYTICS", "lotolab_quina_analytics_v2")
DB_NAME_APP = os.getenv("DB_DATABASE", "lotolab_app_v2")

DATABASE_URL = os.getenv("QUINA_DATABASE_URL", f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME_ANALYTICS}")
APP_DATABASE_URL = os.getenv("APP_DATABASE_URL", f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME_APP}")

engine = create_engine(DATABASE_URL, echo=False)
engine_app = create_engine(APP_DATABASE_URL, echo=False)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
SessionLocalApp = sessionmaker(autocommit=False, autoflush=False, bind=engine_app)

Base = declarative_base()

def init_databases():
    """Cria o banco de dados analítico da Quina caso ele ainda não exista"""
    from sqlalchemy import text
    root_url = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}"
    root_engine = create_engine(root_url, echo=False)
    with root_engine.connect() as conn:
        conn.execute(text(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME_ANALYTICS}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"))
        conn.commit()

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

def get_db_app():
    db = SessionLocalApp()
    try:
        yield db
    finally:
        db.close()
