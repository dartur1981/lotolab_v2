FROM python:3.11-slim

# Instala bibliotecas de runtime necessárias (ex: OpenMP para scikit-learn)
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    libgomp1 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copia dependências primeiro para aproveitar o cache de camadas do Docker
COPY python/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copia o código da aplicação Python (incluindo o módulo lotofacil e modelos)
COPY python/ /app/

EXPOSE 5000

# Copia script de entrypoint com auto-setup do banco
COPY docker/python/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["uvicorn", "lotofacil.main:app", "--host", "0.0.0.0", "--port", "5000"]
