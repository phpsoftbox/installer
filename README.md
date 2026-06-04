# PhpSoftBox Installer

## Русский

Installer/launcher для Workspace и PhpSoftBox-приложений.

`phpsoftbox` устанавливается как полное имя бинарника. Shell installer может дополнительно создать короткий alias `psb -> phpsoftbox`, если имя `psb` свободно.

### Установка

Требования для локальной установки: `php-cli`, `ext-mbstring`, Composer.

На macOS Composer часто ставят через Homebrew:

```bash
brew install php composer
composer --version
```

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/phpsoftbox/installer/master/install.sh)"
```

или через Composer:

```bash
composer global require \
  phpsoftbox/installer:dev-master \
  phpsoftbox/cli-app:dev-master \
  phpsoftbox/error-formatter:dev-master \
  --prefer-stable
```

Проверка:

```bash
export PATH="$(composer -q global config bin-dir --absolute):$PATH"
phpsoftbox list
```

Чтобы команда была доступна в новых терминалах, добавьте Composer global bin в профиль
вашего shell. Путь лучше брать у Composer, потому что на macOS он может отличаться
в зависимости от способа установки:

```bash
composer_bin="$(composer -q global config bin-dir --absolute)"
```

Для zsh, который используется в macOS по умолчанию:

```bash
composer_bin="$(composer -q global config bin-dir --absolute)"
{
  echo "export PATH=\"${composer_bin}:\$PATH\""
} >> ~/.zshrc
source ~/.zshrc
```

Для bash:

```bash
composer_bin="$(composer -q global config bin-dir --absolute)"
{
  echo "export PATH=\"${composer_bin}:\$PATH\""
} >> ~/.bashrc
source ~/.bashrc
```

Если на macOS используется login-shell bash, вместо `~/.bashrc` может понадобиться
`~/.bash_profile`.

### Обновление

```bash
phpsoftbox self-update
```

Команду можно запускать из любой директории.

### Автокомплит

Поддерживаются zsh и Bash completion для имен команды `phpsoftbox` и `psb`.

Для zsh:

```bash
composer_home="$(composer -q global config home)"
{
  echo 'autoload -Uz compinit && compinit'
  echo "source \"${composer_home}/vendor/phpsoftbox/installer/bin/_phpsoftbox_zsh_completion\""
} >> ~/.zshrc
source ~/.zshrc
```

Если ранее уже была добавлена строка вида
`source "$(composer global config bin-dir --absolute)/_phpsoftbox_zsh_completion"`,
удалите её из `~/.zshrc`: в `bin-dir` Composer может положить proxy-файл, который нельзя
использовать через `source`.

Для Bash:

```bash
source "$(composer -q global config home)/vendor/phpsoftbox/installer/bin/_phpsoftbox_completion"
```

Чтобы включить Bash completion постоянно:

```bash
echo 'source "$(composer -q global config home)/vendor/phpsoftbox/installer/bin/_phpsoftbox_completion"' >> ~/.bashrc
source ~/.bashrc
```

На macOS с bash login-shell используйте `~/.bash_profile` вместо `~/.bashrc`.

### Быстрый Старт

```bash
phpsoftbox workspace:install Workspace
cd Workspace
phpsoftbox workspace:init
phpsoftbox new backend
phpsoftbox init
phpsoftbox up
phpsoftbox vite-dev
```

`phpsoftbox workspace:install [dir]` можно запускать из любой директории. Все остальные команды нужно запускать из корня Workspace, где лежат `compose.yml`, `.env`, `.workspace.ini` и папка `local`.

Workspace устанавливается как scaffold-копия без `.git`, чтобы его можно было менять под конкретный проект.

### Команды

Команды `init`, `config`, `up`, `down`, `down-clear`, `build`, `build-no-cache`, `ps`, `logs`, `shell`, `composer-install`, `composer-update`, `test`, `cs-check`, `cs-fix`, `yarn-install`, `yarn-build` и `vite-dev` поддерживают `-p|--profiles` для разового переопределения профилей Workspace.

| Команда | Где запускать | Описание |
| --- | --- | --- |
| `phpsoftbox workspace:install [dir]` | Любая директория | Устанавливает Workspace scaffold в целевую директорию. По умолчанию используется `Workspace`; доступны `--source/-s`, `--branch/-b`, `--force/-f`. |
| `phpsoftbox workspace:init` | Корень Workspace | Создает локальные `.env`, `.workspace.ini` и `local/.gitkeep` из файлов-примеров. `--force/-f` перезаписывает существующие файлы. |
| `phpsoftbox new <service>` | Корень Workspace | Создает AppBackend-сервис в `local/<service>` и обновляет `.env` для выбранного сервиса. Доступны `--source/-s`, `--branch/-b`, `--force/-f`. |
| `phpsoftbox self-update` | Любая директория | Обновляет глобально установленный installer через Composer. |
| `phpsoftbox init` | Корень Workspace | Выполняет первичную подготовку приложения: генерирует `APP_KEY`, запускает `composer install` и `yarn install`. |
| `phpsoftbox config` | Корень Workspace | Показывает итоговую Docker Compose-конфигурацию с учетом активных профилей. |
| `phpsoftbox up` | Корень Workspace | Запускает сервисы активных профилей в фоне. |
| `phpsoftbox down` | Корень Workspace | Останавливает сервисы Workspace и удаляет orphan-контейнеры. |
| `phpsoftbox down-clear` | Корень Workspace | Останавливает сервисы Workspace, удаляет orphan-контейнеры и volumes. |
| `phpsoftbox build` | Корень Workspace | Собирает Docker images для активных профилей с `--pull`. |
| `phpsoftbox build-no-cache` | Корень Workspace | Собирает Docker images для активных профилей с `--pull --no-cache`. |
| `phpsoftbox ps` | Корень Workspace | Показывает состояние Docker Compose-сервисов. |
| `phpsoftbox logs` | Корень Workspace | Показывает поток логов Docker Compose с последними 200 строками. |
| `phpsoftbox shell` | Корень Workspace | Открывает `bash` внутри одноразового `php-cli` контейнера. |
| `phpsoftbox composer-install` | Корень Workspace | Запускает `composer install` внутри `php-cli` контейнера. |
| `phpsoftbox composer-update` | Корень Workspace | Запускает `composer update` внутри `php-cli` контейнера. |
| `phpsoftbox test` | Корень Workspace | Запускает `composer test` внутри `php-cli` контейнера. |
| `phpsoftbox cs-check` | Корень Workspace | Запускает проверку кодстайла через `composer cs:check`. |
| `phpsoftbox cs-fix` | Корень Workspace | Запускает автоисправление кодстайла через `composer cs:fix`. |
| `phpsoftbox yarn-install` | Корень Workspace | Запускает `yarn install` внутри `php-cli` контейнера. |
| `phpsoftbox yarn-build` | Корень Workspace | Запускает `yarn build` внутри `php-cli` контейнера. |
| `phpsoftbox vite-dev` | Корень Workspace | Запускает Vite dev server внутри `php-fpm` контейнера. |
| `phpsoftbox profiles:list` | Корень Workspace | Показывает дефолтные профили из `.workspace.ini`. |
| `phpsoftbox profiles:set <profiles...>` | Корень Workspace | Полностью заменяет дефолтный набор профилей в `.workspace.ini`. |
| `phpsoftbox profiles:add <profiles...>` | Корень Workspace | Добавляет профили в дефолтный набор без дублей. |
| `phpsoftbox profiles:remove <profiles...>` | Корень Workspace | Удаляет профили из дефолтного набора. |

## English

Installer/launcher for Workspace and PhpSoftBox applications.

`phpsoftbox` is installed as the full binary name. The shell installer may also create a short `psb -> phpsoftbox` alias when the `psb` name is available.

### Installation

Local installation requirements: `php-cli`, `ext-mbstring`, Composer.

On macOS Composer is often installed through Homebrew:

```bash
brew install php composer
composer --version
```

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/phpsoftbox/installer/master/install.sh)"
```

or with Composer:

```bash
composer global require \
  phpsoftbox/installer:dev-master \
  phpsoftbox/cli-app:dev-master \
  phpsoftbox/error-formatter:dev-master \
  --prefer-stable
```

Check the installation:

```bash
export PATH="$(composer -q global config bin-dir --absolute):$PATH"
phpsoftbox list
```

To make the command available in new terminal sessions, add Composer global bin to
your shell profile. Ask Composer for the path because it can differ on macOS depending
on how Composer was installed:

```bash
composer_bin="$(composer -q global config bin-dir --absolute)"
```

For zsh, the default shell on modern macOS:

```bash
composer_bin="$(composer -q global config bin-dir --absolute)"
{
  echo "export PATH=\"${composer_bin}:\$PATH\""
} >> ~/.zshrc
source ~/.zshrc
```

For Bash:

```bash
composer_bin="$(composer -q global config bin-dir --absolute)"
{
  echo "export PATH=\"${composer_bin}:\$PATH\""
} >> ~/.bashrc
source ~/.bashrc
```

If Bash is used as a login shell on macOS, use `~/.bash_profile` instead of `~/.bashrc`.

### Update

```bash
phpsoftbox self-update
```

The command can be run from any directory.

### Autocomplete

zsh and Bash completion are available for both `phpsoftbox` and `psb`.

For zsh:

```bash
composer_home="$(composer -q global config home)"
{
  echo 'autoload -Uz compinit && compinit'
  echo "source \"${composer_home}/vendor/phpsoftbox/installer/bin/_phpsoftbox_zsh_completion\""
} >> ~/.zshrc
source ~/.zshrc
```

If you already added a line like
`source "$(composer global config bin-dir --absolute)/_phpsoftbox_zsh_completion"`,
remove it from `~/.zshrc`: Composer may place a proxy file in `bin-dir`, and that proxy
cannot be used through `source`.

For Bash:

```bash
source "$(composer -q global config home)/vendor/phpsoftbox/installer/bin/_phpsoftbox_completion"
```

To enable Bash completion permanently:

```bash
echo 'source "$(composer -q global config home)/vendor/phpsoftbox/installer/bin/_phpsoftbox_completion"' >> ~/.bashrc
source ~/.bashrc
```

On macOS with Bash as a login shell, use `~/.bash_profile` instead of `~/.bashrc`.

### Quick Start

```bash
phpsoftbox workspace:install Workspace
cd Workspace
phpsoftbox workspace:init
phpsoftbox new backend
phpsoftbox init
phpsoftbox up
phpsoftbox vite-dev
```

`phpsoftbox workspace:install [dir]` can be run from any directory. All other commands must be run from the Workspace root, where `compose.yml`, `.env`, `.workspace.ini`, and the `local` directory exist.

Workspace is installed as a scaffold copy without `.git`, so it can be customized for a specific project.

### Commands

The `init`, `config`, `up`, `down`, `down-clear`, `build`, `build-no-cache`, `ps`, `logs`, `shell`, `composer-install`, `composer-update`, `test`, `cs-check`, `cs-fix`, `yarn-install`, `yarn-build`, and `vite-dev` commands support `-p|--profiles` for one-off Workspace profile overrides.

| Command | Run from | Description |
| --- | --- | --- |
| `phpsoftbox workspace:install [dir]` | Any directory | Installs the Workspace scaffold into the target directory. Defaults to `Workspace`; supports `--source/-s`, `--branch/-b`, `--force/-f`. |
| `phpsoftbox workspace:init` | Workspace root | Creates local `.env`, `.workspace.ini`, and `local/.gitkeep` from example files. `--force/-f` overwrites existing files. |
| `phpsoftbox new <service>` | Workspace root | Creates an AppBackend service in `local/<service>` and updates `.env` for that service. Supports `--source/-s`, `--branch/-b`, `--force/-f`. |
| `phpsoftbox self-update` | Any directory | Updates the globally installed installer through Composer. |
| `phpsoftbox init` | Workspace root | Runs first-time application setup: generates `APP_KEY`, runs `composer install`, and runs `yarn install`. |
| `phpsoftbox config` | Workspace root | Shows the final Docker Compose configuration with active profiles applied. |
| `phpsoftbox up` | Workspace root | Starts services for active profiles in detached mode. |
| `phpsoftbox down` | Workspace root | Stops Workspace services and removes orphan containers. |
| `phpsoftbox down-clear` | Workspace root | Stops Workspace services and removes orphan containers and volumes. |
| `phpsoftbox build` | Workspace root | Builds Docker images for active profiles with `--pull`. |
| `phpsoftbox build-no-cache` | Workspace root | Builds Docker images for active profiles with `--pull --no-cache`. |
| `phpsoftbox ps` | Workspace root | Shows Docker Compose service status. |
| `phpsoftbox logs` | Workspace root | Follows Docker Compose logs with the last 200 lines. |
| `phpsoftbox shell` | Workspace root | Opens `bash` inside a one-off `php-cli` container. |
| `phpsoftbox composer-install` | Workspace root | Runs `composer install` inside the `php-cli` container. |
| `phpsoftbox composer-update` | Workspace root | Runs `composer update` inside the `php-cli` container. |
| `phpsoftbox test` | Workspace root | Runs `composer test` inside the `php-cli` container. |
| `phpsoftbox cs-check` | Workspace root | Runs the code style check through `composer cs:check`. |
| `phpsoftbox cs-fix` | Workspace root | Runs the code style fixer through `composer cs:fix`. |
| `phpsoftbox yarn-install` | Workspace root | Runs `yarn install` inside the `php-cli` container. |
| `phpsoftbox yarn-build` | Workspace root | Runs `yarn build` inside the `php-cli` container. |
| `phpsoftbox vite-dev` | Workspace root | Starts the Vite dev server inside the `php-fpm` container. |
| `phpsoftbox profiles:list` | Workspace root | Shows default profiles from `.workspace.ini`. |
| `phpsoftbox profiles:set <profiles...>` | Workspace root | Replaces the default profile set in `.workspace.ini`. |
| `phpsoftbox profiles:add <profiles...>` | Workspace root | Adds profiles to the default set without duplicates. |
| `phpsoftbox profiles:remove <profiles...>` | Workspace root | Removes profiles from the default set. |
