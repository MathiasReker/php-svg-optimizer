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

# --- TYPE COVERAGE BADGE ---
TYPE_COVERAGE_JSON=$(./vendor/bin/phpstan analyse -c phpstan-type-coverage.neon --no-progress --error-format=json 2>/dev/null) || true

TYPE_TOTAL_FILLED=0
TYPE_TOTAL_POSSIBLE=0

while IFS= read -r LINE; do
    [[ -z $LINE ]] && continue
    TYPE_PCT=$(echo "$LINE" | grep -oE '[0-9]+\.[0-9]+' | head -1)
    TYPE_TOTAL=$(echo "$LINE" | grep -oE 'out of [0-9]+' | grep -oE '[0-9]+')
    TYPE_FILLED=$(awk -v p="$TYPE_PCT" -v t="$TYPE_TOTAL" 'BEGIN { printf "%.0f", (p / 100) * t }')
    TYPE_TOTAL_FILLED=$((TYPE_TOTAL_FILLED + TYPE_FILLED))
    TYPE_TOTAL_POSSIBLE=$((TYPE_TOTAL_POSSIBLE + TYPE_TOTAL))
done < <(echo "$TYPE_COVERAGE_JSON" | grep -oE '(Class constant|Param|Property|Return) type coverage is [0-9]+\.[0-9]+ % out of [0-9]+ possible')

TYPE_PERCENT_INT=0
(( TYPE_TOTAL_POSSIBLE > 0 )) && TYPE_PERCENT_INT=$(awk -v f="$TYPE_TOTAL_FILLED" -v t="$TYPE_TOTAL_POSSIBLE" 'BEGIN { printf "%.0f", (f / t) * 100 }')

TYPE_COLOR="red"
(( TYPE_PERCENT_INT >= 80 )) && TYPE_COLOR="green"
(( TYPE_PERCENT_INT >= 50 && TYPE_PERCENT_INT < 80 )) && TYPE_COLOR="yellow"

curl -s -o dev/artifacts/type-coverage.svg \
  "https://img.shields.io/badge/type_coverage-${TYPE_PERCENT_INT}%25-${TYPE_COLOR}?style=flat"

# --- MUTATION SCORE BADGE ---
MUTATION_SUMMARY_JSON=$(mktemp)
./vendor/bin/infection --threads=max --no-interaction --no-progress --logger-summary-json="$MUTATION_SUMMARY_JSON" > /dev/null 2>&1 || true

MUTATION_PERCENT_INT=0
if [[ -s $MUTATION_SUMMARY_JSON ]]; then
    MUTATION_MSI=$(grep -oE '"msi":[0-9.]+' "$MUTATION_SUMMARY_JSON" | grep -oE '[0-9.]+')
    [[ -n $MUTATION_MSI ]] && MUTATION_PERCENT_INT=$(awk -v m="$MUTATION_MSI" 'BEGIN { printf "%.0f", m }')
fi

MUTATION_COLOR="red"
(( MUTATION_PERCENT_INT >= 80 )) && MUTATION_COLOR="green"
(( MUTATION_PERCENT_INT >= 50 && MUTATION_PERCENT_INT < 80 )) && MUTATION_COLOR="yellow"

curl -s -o dev/artifacts/mutation-score.svg \
  "https://img.shields.io/badge/mutation_score-${MUTATION_PERCENT_INT}%25-${MUTATION_COLOR}?style=flat"

# --- MUTATION COVERAGE BADGE ---
MUTATION_COVERAGE_PERCENT_INT=0
if [[ -s $MUTATION_SUMMARY_JSON ]]; then
    MUTATION_COVERAGE=$(grep -oE '"mutationCodeCoverage":[0-9.]+' "$MUTATION_SUMMARY_JSON" | grep -oE '[0-9.]+')
    [[ -n $MUTATION_COVERAGE ]] && MUTATION_COVERAGE_PERCENT_INT=$(awk -v m="$MUTATION_COVERAGE" 'BEGIN { printf "%.0f", m }')
fi
rm -f "$MUTATION_SUMMARY_JSON"

MUTATION_COVERAGE_COLOR="red"
(( MUTATION_COVERAGE_PERCENT_INT >= 80 )) && MUTATION_COVERAGE_COLOR="green"
(( MUTATION_COVERAGE_PERCENT_INT >= 50 && MUTATION_COVERAGE_PERCENT_INT < 80 )) && MUTATION_COVERAGE_COLOR="yellow"

curl -s -o dev/artifacts/mutation-coverage.svg \
  "https://img.shields.io/badge/mutation_coverage-${MUTATION_COVERAGE_PERCENT_INT}%25-${MUTATION_COVERAGE_COLOR}?style=flat"

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
