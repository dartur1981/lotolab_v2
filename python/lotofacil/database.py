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

# Lê estritamente as variáveis configuradas no web/.env (sem valores marretados)
DB_HOST = os.getenv("DB_HOST")
DB_PORT = os.getenv("DB_PORT", "3306")
DB_USER = os.getenv("DB_USERNAME")
DB_PASS = os.getenv("DB_PASSWORD")
DB_NAME_ANALYTICS = os.getenv("DB_DATABASE_ANALYTICS")
DB_NAME_APP = os.getenv("DB_DATABASE")

# Validação estrita: se faltar configuração, avisa qual campo do .env está faltando
obrigatorias = {
    "DB_HOST": DB_HOST,
    "DB_USERNAME": DB_USER,
    "DB_PASSWORD": DB_PASS,
    "DB_DATABASE_ANALYTICS": DB_NAME_ANALYTICS,
    "DB_DATABASE": DB_NAME_APP
}
ausentes = [k for k, v in obrigatorias.items() if not v]
if ausentes:
    raise RuntimeError(
        f"Erro de configuração do banco: As variáveis [{', '.join(ausentes)}] "
        "não foram encontradas no web/.env. Preencha os dados do banco no web/.env!"
    )

DATABASE_URL = os.getenv("DATABASE_URL", f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME_ANALYTICS}")
APP_DATABASE_URL = os.getenv("APP_DATABASE_URL", f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME_APP}")

engine = create_engine(DATABASE_URL, echo=False)
engine_app = create_engine(APP_DATABASE_URL, echo=False)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
SessionLocalApp = sessionmaker(autocommit=False, autoflush=False, bind=engine_app)

Base = declarative_base()

def init_databases():
    """Cria os bancos de dados caso eles ainda não existam no servidor MySQL"""
    from sqlalchemy import text
    root_url = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}"
    root_engine = create_engine(root_url, echo=False)
    with root_engine.connect() as conn:
        conn.execute(text(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME_ANALYTICS}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"))
        conn.execute(text(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME_APP}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"))
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
