#!/usr/bin/env bash

set -euo pipefail

for command in composer pv; do
    if ! command -v "$command" > /dev/null; then
        echo "$command is required to record the demo." >&2
        exit 1
    fi
done

repository=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
demo_dir=$(mktemp -d)
trap 'rm -rf "$demo_dir"' EXIT

export COMPOSER_ROOT_VERSION=1.0.0

version=$(git -C "$repository" describe --tags --exact-match 2> /dev/null || true)
repository_options=''
if [[ -z "$version" ]]; then
    repository_options=",\"options\":{\"versions\":{\"mathiasreker/php-svg-optimizer\":\"${DEMO_VERSION:-10.0.0}\"}}"
fi

cd "$demo_dir"
composer init --quiet --no-interaction --name=demo/demo --stability=dev
composer config prefer-stable true
composer config repositories.php-svg-optimizer --json "{\"type\":\"path\",\"url\":\"$repository\"$repository_options}"
cp -R "$repository/dev/samples" svg

export TERM="${TERM:-xterm-256color}"

type_line() {
    echo "> $1" | pv -qL 10
}

type_line "# Install php-svg-optimizer with Composer"
sleep 1
type_line "composer require mathiasreker/php-svg-optimizer"
composer require mathiasreker/php-svg-optimizer --ansi --no-interaction

echo ">"
type_line "# Optimize every SVG in a directory with one command"
type_line "vendor/bin/svg-optimizer svg"
vendor/bin/svg-optimizer --ansi svg

echo ">"
type_line "# Done! Smaller, cleaner SVGs."
sleep 2
