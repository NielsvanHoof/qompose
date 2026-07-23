#!/usr/bin/env bash
# Pull OCR IAM user access keys from Pulumi secrets into apps/platform/.env
# (Root cannot assume IAM roles — use this instead of sts:AssumeRole.)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLATFORM_ROOT="$(cd "$ROOT/.." && pwd)"
ENV_FILE="${PLATFORM_ENV_FILE:-$PLATFORM_ROOT/.env}"

cd "$ROOT"

if [[ -x "$ROOT/node_modules/.bin/pulumi" ]]; then
  PULUMI="$ROOT/node_modules/.bin/pulumi"
elif command -v pulumi >/dev/null 2>&1; then
  PULUMI="$(command -v pulumi)"
else
  echo "Pulumi CLI not found. Run npm ci in apps/platform/infra first." >&2
  exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing $ENV_FILE" >&2
  exit 1
fi

ACCESS_KEY="$("$PULUMI" stack output ocrAccessKeyId --show-secrets)"
SECRET_KEY="$("$PULUMI" stack output ocrSecretAccessKey --show-secrets)"

ACCESS_KEY="$(printf '%s' "$ACCESS_KEY" | tr -d '\r' | grep -Eo 'AKIA[0-9A-Z]{16}' | head -1)"
SECRET_KEY="$(printf '%s' "$SECRET_KEY" | tr -d '\r' | awk 'NF{print; exit}')"

if [[ -z "$ACCESS_KEY" || -z "$SECRET_KEY" ]]; then
  echo "Could not read ocrAccessKeyId / ocrSecretAccessKey. Run npm run up first." >&2
  exit 1
fi

upsert_env() {
  local key="$1"
  local value="$2"
  local tmp
  tmp="$(mktemp)"
  awk -v k="$key" -v v="$value" '
    BEGIN { done=0 }
    index($0, k"=")==1 { print k"="v; done=1; next }
    { print }
    END { if (!done) print k"="v }
  ' "$ENV_FILE" > "$tmp"
  mv "$tmp" "$ENV_FILE"
}

upsert_env AWS_ACCESS_KEY_ID "$ACCESS_KEY"
upsert_env AWS_SECRET_ACCESS_KEY "$SECRET_KEY"
# Long-lived IAM user keys do not use a session token.
upsert_env AWS_SESSION_TOKEN ""

# Real AWS (not MinIO).
upsert_env AWS_ENDPOINT ""
upsert_env AWS_URL ""
upsert_env AWS_USE_PATH_STYLE_ENDPOINT false

echo "Wrote OCR IAM user credentials to $ENV_FILE"
echo "Restart Sail / textract:consume so PHP picks up the new env."
