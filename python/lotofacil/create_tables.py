import sys
import os

_parent_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if _parent_dir not in sys.path:
    sys.path.insert(0, _parent_dir)

from lotofacil import models
from lotofacil.database import engine

models.Base.metadata.create_all(bind=engine)
print("Tabelas criadas com sucesso.")
