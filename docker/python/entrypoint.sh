#!/bin/bash
set -e

echo "[Python Entrypoint] Verificando e sincronizando tabelas do banco..."
python lotofacil/setup_tables.py || echo "[Python Entrypoint] Aviso: Falha ao rodar setup_tables na inicialização. Continuando..."

exec "$@"
