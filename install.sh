#!/usr/bin/env bash
set -euo pipefail

composer global require \
  phpsoftbox/installer:dev-master \
  phpsoftbox/cli-app:dev-master \
  phpsoftbox/error-formatter:dev-master \
  --prefer-stable

composer_bin="$(composer -q global config bin-dir --absolute)"
composer_home="$(composer -q global config home)"
zsh_completion="${composer_home}/vendor/phpsoftbox/installer/bin/_phpsoftbox_zsh_completion"
bash_completion="${composer_home}/vendor/phpsoftbox/installer/bin/_phpsoftbox_completion"

if [[ ":${PATH}:" != *":${composer_bin}:"* ]]; then
  printf 'Add Composer global bin directory to PATH: %s\n' "${composer_bin}"
fi

if [[ -f "${zsh_completion}" && -w "${composer_bin}" ]]; then
  cp "${zsh_completion}" "${composer_bin}/_phpsoftbox_zsh_completion"
  chmod 0644 "${composer_bin}/_phpsoftbox_zsh_completion"
fi

if [[ -f "${bash_completion}" && -w "${composer_bin}" ]]; then
  cp "${bash_completion}" "${composer_bin}/_phpsoftbox_completion"
  chmod 0644 "${composer_bin}/_phpsoftbox_completion"
fi

alias_created=0
if [[ ! -e "${composer_bin}/psb" && -w "${composer_bin}" ]]; then
  ln -s "${composer_bin}/phpsoftbox" "${composer_bin}/psb"
  alias_created=1
fi

printf 'Installed: phpsoftbox\n'
printf 'Composer bin: %s\n' "${composer_bin}"
printf 'Composer home: %s\n' "${composer_home}"
printf 'Zsh completion file: %s\n' "${zsh_completion}"
printf 'Bash completion file: %s\n' "${bash_completion}"
printf '\n'
printf 'For zsh, add to ~/.zshrc:\n'
printf '  export PATH="%s:$PATH"\n' "${composer_bin}"
printf '  autoload -Uz compinit && compinit\n'
printf '  source "%s"\n' "${zsh_completion}"
printf '\n'
printf 'For bash, add to ~/.bashrc or ~/.bash_profile:\n'
printf '  export PATH="%s:$PATH"\n' "${composer_bin}"
printf '  source "%s"\n' "${bash_completion}"
printf '\n'
if [[ "${alias_created}" == "1" ]]; then
  printf 'Alias: psb -> phpsoftbox\n'
elif [[ -e "${composer_bin}/psb" ]]; then
  printf 'Alias not created: %s already exists. Use phpsoftbox.\n' "${composer_bin}/psb"
fi
