#!/usr/bin/env bash

set -e

export TERM="${TERM:-xterm-256color}"
export CLICOLOR_FORCE=1

type_effect() {
  echo "$1" | pv -qL 10
}

type_effect "> # Install php-svg-optimizer with Composer"
sleep 1

cd .. || { echo "Failed to leave the repository directory"; exit 1; }

type_effect "> composer require mathiasreker/php-svg-optimizer"
composer require mathiasreker/php-svg-optimizer --ansi

echo ">"
echo ">"

type_effect "> # Optimize every SVG in a directory with one command"
type_effect "> vendor/bin/svg-optimizer --with-all-rules --ansi process php-svg-optimizer/dev/resources/svg"

vendor/bin/svg-optimizer --with-all-rules --ansi process php-svg-optimizer/dev/resources/svg

echo ">"
echo ">"

type_effect "> # Done! Smaller, cleaner, safer SVGs."

sleep 2
