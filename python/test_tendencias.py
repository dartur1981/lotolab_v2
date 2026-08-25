import os
from database import SessionLocal
from analisador_tendencias import analisar_tendencias

db = SessionLocal()
log_path = "d:/SISTEMAS-PRIVATE/lotolab_app_v2/logs/test_tendencias.txt"
if os.path.exists(log_path):
    os.remove(log_path)
    
analisar_tendencias(log_path, db)

with open(log_path, "r") as f:
    print(f.read())
