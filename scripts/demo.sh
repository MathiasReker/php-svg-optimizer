#!/usr/bin/env bash

echo "> # Welcome to PHP SVG Optimizer!" | pv -qL 10
sleep 1
echo "> # See how easy it is to optimize SVG files" | pv -qL 10
sleep 1
echo "> # Let's start by installing the library" | pv -qL 10
sleep 1
cd ..
echo "> composer require mathiasreker/php-svg-optimizer" | pv -qL 10
composer require mathiasreker/php-svg-optimizer
echo ">"
echo ">"
echo "> # You're ready to optimize your SVG files!" | pv -qL 10
echo "> # Run the following command to process the SVG files in a directory:" | pv -qL 10
echo "> vendor/bin/svg-optimizer process php-svg-optimizer/assets/logos" | pv -qL 10
vendor/bin/svg-optimizer process php-svg-optimizer/assets/logos
echo ">"
echo ">"
echo "> # That's it! Your SVG files are now optimized" | pv -qL 10
sleep 30
