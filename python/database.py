from sqlalchemy import create_engine
from sqlalchemy.orm import declarative_base, sessionmaker

DATABASE_URL = "mysql+pymysql://developer:d3v3l0p3r@127.0.0.1:3306/lotolab_lotofacil_analytics"
APP_DATABASE_URL = "mysql+pymysql://developer:d3v3l0p3r@127.0.0.1:3306/lotolab_app_v2"

engine = create_engine(DATABASE_URL, echo=False)
engine_app = create_engine(APP_DATABASE_URL, echo=False)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
SessionLocalApp = sessionmaker(autocommit=False, autoflush=False, bind=engine_app)

Base = declarative_base()

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
