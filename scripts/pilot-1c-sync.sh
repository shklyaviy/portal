#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

env_get() {
  local key="$1"
  local default="${2:-}"
  if [[ -f .env ]]; then
    local line
    line="$(grep -E "^${key}=" .env | tail -n1 || true)"
    if [[ -n "$line" ]]; then
      local value="${line#*=}"
      value="${value%\"}"
      value="${value#\"}"
      value="${value%\'}"
      value="${value#\'}"
      printf '%s' "$value"
      return 0
    fi
  fi
  printf '%s' "$default"
}

BASE_URL="$(env_get APP_URL 'http://127.0.0.1:8000')"
BASE_URL="${BASE_URL%/}"
ENDPOINT="${BASE_URL}/api/1c/exchange"

USER="$(env_get ONE_C_USER '1c')"
PASS="$(env_get ONE_C_PASSWORD 'secret')"
TOKEN="$(env_get ONE_C_TOKEN '')"

# Ensure local defaults exist for developer convenience
if ! grep -q '^ONE_C_USER=' .env 2>/dev/null; then
  printf '\nONE_C_USER=1c\nONE_C_PASSWORD=secret\nONE_C_TOKEN=\n' >> .env
  USER='1c'
  PASS='secret'
fi

PAYLOAD="$(mktemp)"
trap 'rm -f "$PAYLOAD"' EXIT

cat >"$PAYLOAD" <<'JSON'
{
  "sync": { "mode": "pilot", "batchId": "pilot-local" },
  "priceTypes": [
    { "externalId": "retail", "name": "Розничная", "isDefault": true }
  ],
  "categories": [
    {
      "externalId": "cat-krovlya",
      "name": "Кровля",
      "slug": "krovlya"
    },
    {
      "externalId": "cat-metallocherepitsa",
      "name": "Металлочерепица",
      "slug": "krovlya/metallocherepitsa",
      "parentExternalId": "cat-krovlya"
    }
  ],
  "products": [
    {
      "externalId": "1c-mt-monterrey-05-ral8017",
      "name": "Металлочерепица Monterrey 0.5 мм RAL 8017",
      "sku": "MT-MONT-05-8017",
      "slug": "krovlya/metallocherepitsa/monterrey-05-ral-8017",
      "categoryExternalId": "cat-metallocherepitsa",
      "price": 689,
      "currency": "RUB",
      "unit": "м²",
      "prices": [
        { "priceTypeExternalId": "retail", "amount": 689, "currency": "RUB" }
      ],
      "attributes": [
        { "name": "Толщина", "value": "0.5 мм" },
        { "name": "Цвет RAL", "value": "8017" }
      ]
    }
  ]
}
JSON

echo "POST $ENDPOINT"

if [[ -n "$TOKEN" ]]; then
  curl -sS \
    -H "x-1c-token: ${TOKEN}" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json' \
    --data @"$PAYLOAD" \
    "$ENDPOINT"
else
  curl -sS \
    -u "${USER}:${PASS}" \
    -H 'Content-Type: application/json' \
    -H 'Accept: application/json' \
    --data @"$PAYLOAD" \
    "$ENDPOINT"
fi
echo
