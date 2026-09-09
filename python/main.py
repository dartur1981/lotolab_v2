import sys
import os

# Adiciona o diretório atual ao sys.path
current_dir = os.path.dirname(os.path.abspath(__file__))
if current_dir not in sys.path:
    sys.path.insert(0, current_dir)

# Importa a aplicação da Lotofácil
from lotofacil.main import app

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("lotofacil.main:app", host="127.0.0.1", port=5000, reload=True)
