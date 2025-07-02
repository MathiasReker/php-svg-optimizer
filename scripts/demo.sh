#!/usr/bin/env bash

set -e

type_effect() {
  echo "$1" | pv -qL 10
}

type_effect "> # Welcome to PHP SVG Optimizer!"
sleep 1

type_effect "> # See how easy it is to optimize SVG files"
sleep 1

type_effect "> # Let's start by installing the library"
sleep 1

cd .. || { echo "Failed to change directory"; exit 1; }

type_effect "> composer require mathiasreker/php-svg-optimizer"
composer require mathiasreker/php-svg-optimizer

echo ">"
echo ">"

type_effect "> # You're ready to optimize your SVG files!"
type_effect "> # Run the following command to process the SVG files in a directory:"
type_effect "> vendor/bin/svg-optimizer process php-svg-optimizer/assets/logos"

vendor/bin/svg-optimizer process php-svg-optimizer/assets/logos

echo ">"
echo ">"

type_effect "> # That's it! Your SVG files are now optimized"

sleep 2
