import os
import sys

_parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from lotofacil.database import SessionLocal
from lotofacil.analisador_tendencias import analisar_tendencias

db = SessionLocal()
log_path = "d:/SISTEMAS-PRIVATE/lotolab_app_v2/logs/test_tendencias.txt"
if os.path.exists(log_path):
    os.remove(log_path)
    
analisar_tendencias(log_path, db)

with open(log_path, "r") as f:
    print(f.read())
