#!/usr/bin/env bash
set -euo pipefail

mkdir -p dev/artifacts

# --- FILE SIZE BADGE ---
FILES=(bin src LICENSE)
SIZE_KB=$(du -sk "${FILES[@]}" 2>/dev/null | awk '{sum+=$1} END {print sum}')

curl -s -o dev/artifacts/filesize.svg \
  "https://img.shields.io/badge/size-${SIZE_KB}KB-brightgreen?style=flat"

# --- CODE COVERAGE BADGE ---
FRACTION=$(./vendor/bin/phpunit --coverage-text | grep 'Lines:' | head -1 | awk '{print $3}')

[[ $FRACTION == .* ]] && FRACTION="0$FRACTION"

PERCENT_INT=$(printf "%.0f" "$(echo "$FRACTION * 100" | awk '{printf "%0.0f", $1}')")

COLOR="red"
(( PERCENT_INT >= 80 )) && COLOR="green"
(( PERCENT_INT >= 50 && PERCENT_INT < 80 )) && COLOR="yellow"

curl -s -o dev/artifacts/coverage.svg \
  "https://img.shields.io/badge/code_coverage-${PERCENT_INT}%25-${COLOR}?style=flat"

# --- TESTS & ASSERTIONS ---
PHPUNIT_OUTPUT=$(./vendor/bin/phpunit --colors=never 2>&1)

if echo "$PHPUNIT_OUTPUT" | grep -q 'OK'; then
    TESTS=$(echo "$PHPUNIT_OUTPUT" | grep -Eo '[0-9]+ tests?' | awk '{print $1}')
    ASSERTIONS=$(echo "$PHPUNIT_OUTPUT" | grep -Eo '[0-9]+ assertions?' | awk '{print $1}')
else
    TESTS=0
    ASSERTIONS=0
fi

# --- TESTS BADGE ---
curl -s -o dev/artifacts/tests.svg \
  "https://img.shields.io/badge/tests-${TESTS}-blue?style=flat"

# --- ASSERTIONS BADGE ---
curl -s -o dev/artifacts/assertions.svg \
  "https://img.shields.io/badge/assertions-${ASSERTIONS}-purple?style=flat"

# --- OPTIMIZE SVG BADGES ---
php bin/svg-optimizer -a -q process dev/artifacts
