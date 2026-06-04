#!/usr/bin/env bash
set -euo pipefail

composer global require \
  phpsoftbox/installer:dev-master \
  phpsoftbox/cli-app:dev-master \
  phpsoftbox/error-formatter:dev-master \
  --prefer-stable

composer_bin="$(composer global config bin-dir --absolute)"

if [[ ":${PATH}:" != *":${composer_bin}:"* ]]; then
  printf 'Add Composer global bin directory to PATH: %s\n' "${composer_bin}"
fi

alias_created=0
if [[ ! -e "${composer_bin}/psb" && -w "${composer_bin}" ]]; then
  ln -s "${composer_bin}/phpsoftbox" "${composer_bin}/psb"
  alias_created=1
fi

printf 'Installed: phpsoftbox\n'
printf 'Bash completion: source %s/_phpsoftbox_completion\n' "${composer_bin}"
if [[ "${alias_created}" == "1" ]]; then
  printf 'Alias: psb -> phpsoftbox\n'
elif [[ -e "${composer_bin}/psb" ]]; then
  printf 'Alias not created: %s already exists. Use phpsoftbox.\n' "${composer_bin}/psb"
fi
