#!/usr/bin/env bash

set -euo pipefail

mkdir -p dev/artifacts

# Badge: Filesize
FILES=(bin src composer.json composer.lock LICENSE)
SIZE_KB=$(du -sk "${FILES[@]}" 2>/dev/null | awk '{sum+=$1} END {print sum}')
curl -s -o dev/artifacts/filesize.svg "https://badgen.net/badge/size/${SIZE_KB}KB/green?label=size"

# Badge: Code Coverage
FRACTION=$(./vendor/bin/phpunit --coverage-text | grep 'Lines:' | head -1 | awk '{print $3}')

[[ $FRACTION == .* ]] && FRACTION="0$FRACTION"
PERCENT_INT=$(printf "%.0f" "$(echo "$FRACTION * 100" | awk '{printf "%0.0f", $1}')")

COLOR="red"
(( PERCENT_INT >= 80 )) && COLOR="green"
(( PERCENT_INT >= 50 && PERCENT_INT < 80 )) && COLOR="yellow"

curl -s -o dev/artifacts/coverage.svg "https://badgen.net/badge/code%20coverage/${PERCENT_INT}%25/${COLOR}"

# Optimize svg files
php bin/svg-optimizer -q process dev/artifacts
