#!/usr/bin/env bash
#
# Deploy de producao: preflight -> build/up -> espera healthchecks.
# Nunca pula o preflight — ele e o que impede subir a stack com senha
# placeholder ou com o drive de dados fora do ar.
#
# Uso:
#   ./scripts/deploy.sh            # producao
#   ./scripts/deploy.sh --dev      # testes locais (preflight relaxado)

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

# O .env da raiz e a configuracao real de producao (senhas, porta, caminhos).
# Este script NUNCA copia .env.example.docker/.env.example para .env: isso
# sobrescreveria a configuracao ja feita no servidor. O preflight tambem exige
# um .env legivel; a checagem explicita aqui da a mensagem antes de qualquer
# build/subida.
ENV_FILE="${SICAV_ENV_FILE:-$ROOT/.env}"
if [[ ! -r "$ENV_FILE" ]]; then
    echo "ERRO: arquivo de ambiente nao encontrado/legivel: $ENV_FILE" >&2
    echo "      O deploy nao copia .env.example: use o .env ja configurado na raiz do projeto." >&2
    exit 1
fi
echo "--> Usando configuracao existente: $ENV_FILE (nao sera copiada nem alterada)"

echo "--> Preflight..."
"$ROOT/scripts/preflight.sh" "${1:-}"

echo "--> Build e subida da stack..."
docker compose -f "$ROOT/docker-compose.yml" up -d --build --remove-orphans

# O entrypoint grava /tmp/migration-ok somente se migrate.php terminou sem erro.
migration_ok() {
    docker compose -f "$ROOT/docker-compose.yml" exec -T app \
        test -f /tmp/migration-ok >/dev/null 2>&1
}

echo "--> Aguardando migracao e app/nginx healthy..."
for i in $(seq 1 90); do
    statuses="$(docker compose -f "$ROOT/docker-compose.yml" ps app nginx --format '{{.Service}} {{.Status}}' 2>/dev/null || true)"
    if migration_ok && [[ "$(grep -c 'healthy' <<< "$statuses")" -ge 2 ]]; then
        echo "$statuses"
        echo "OK: deploy concluido (migracao OK)."
        exit 0
    fi
    if [[ "$i" -eq 90 ]]; then
        migration_state="$(migration_ok && echo OK || echo PENDENTE_OU_FALHA)"
        echo "ERRO: app/nginx nao ficaram healthy em 180s (migracao: $migration_state)." >&2
        echo "$statuses" >&2
        docker compose -f "$ROOT/docker-compose.yml" logs app nginx --tail 50 >&2
        exit 1
    fi
    sleep 2
done
