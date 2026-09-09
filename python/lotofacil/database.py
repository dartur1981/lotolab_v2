import os
from sqlalchemy import create_engine
from sqlalchemy.orm import declarative_base, sessionmaker

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = os.getenv("DB_PORT", "3306")
DB_USER = os.getenv("DB_USERNAME", "developer")
DB_PASS = os.getenv("DB_PASSWORD", "d3v3l0p3r")
DB_NAME_ANALYTICS = os.getenv("DB_DATABASE_ANALYTICS", "lotolab_lotofacil_analytics")
DB_NAME_APP = os.getenv("DB_DATABASE", "lotolab_app_v2")

DATABASE_URL = os.getenv("DATABASE_URL", f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME_ANALYTICS}")
APP_DATABASE_URL = os.getenv("APP_DATABASE_URL", f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME_APP}")

engine = create_engine(DATABASE_URL, echo=False)
engine_app = create_engine(APP_DATABASE_URL, echo=False)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
SessionLocalApp = sessionmaker(autocommit=False, autoflush=False, bind=engine_app)

Base = declarative_base()

def init_databases():
    from sqlalchemy import text
    try:
        root_url = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}"
        root_engine = create_engine(root_url, echo=False)
        with root_engine.connect() as conn:
            conn.execute(text(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME_ANALYTICS}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"))
            conn.execute(text(f"CREATE DATABASE IF NOT EXISTS `{DB_NAME_APP}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"))
            conn.commit()
    except Exception as e:
        print(f"Aviso ao inicializar bancos: {e}")

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
