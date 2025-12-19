#!/usr/bin/env bash

set -e

type_effect() {
  echo "$1" | pv -qL 10
}

type_effect "> # Install the library"
sleep 1

cd .. || { echo "Failed to change directory"; exit 1; }

type_effect "> composer require mathiasreker/php-svg-optimizer"
composer require mathiasreker/php-svg-optimizer

echo ">"
echo ">"

type_effect "> # Optimize the SVG files"
type_effect "> vendor/bin/svg-optimizer -a process php-svg-optimizer/dev/resources/svg"

vendor/bin/svg-optimizer -a process php-svg-optimizer/dev/resources/svg

echo ">"
echo ">"

type_effect "> # Done!"

sleep 2
