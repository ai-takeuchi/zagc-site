#!/bin/bash
#
# 動作試験
# $ bash ./scripts/load_env_php.sh --dry-run .env.php

set -euo pipefail

# === 引数処理 ===
DRY_RUN=false
ENV_PHP_FILE=""

for arg in "$@"; do
  case "$arg" in
    --dry-run)
      DRY_RUN=true
      ;;
    *)
      ENV_PHP_FILE="$arg"
      ;;
  esac
done

if [[ -z "$ENV_PHP_FILE" ]]; then
  echo "Usage: $0 [--dry-run] path/to/.env.php" >&2
  exit 1
fi

# PHPファイルから define('KEY', 'VALUE'); を抽出して export KEY=VALUE に変換
while IFS= read -r line; do
  if [[ "$line" =~ ^define\([\'\"]([A-Z0-9_]+)[\'\"]\ *,\ *[\'\"](.*)[\'\"]\)\; ]]; then
    # echo $line
    # echo ${BASH_REMATCH[1]}
    # echo ${BASH_REMATCH[2]}
    key="${BASH_REMATCH[1]}"
    val="${BASH_REMATCH[2]}"
    # PHPのエスケープされたシングルクオートを戻す
    val="${val//\\\'}"
    if $DRY_RUN; then
      echo "export $key='$val'"
    else
      export "$key=$val"
    fi
  fi
done < <(grep "^define(" "$ENV_PHP_FILE")
