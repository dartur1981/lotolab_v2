#!/bin/bash
set -e

mkdir -p /data/historicos /logs /var/www/data/historicos /var/www/logs 2>/dev/null || true
chmod -R 777 /data /logs /var/www/data /var/www/logs 2>/dev/null || true

echo "[Python Entrypoint] Verificando e sincronizando tabelas do banco..."
python lotofacil/setup_tables.py || echo "[Python Entrypoint] Aviso: Falha ao rodar setup_tables na inicialização. Continuando..."

exec "$@"
