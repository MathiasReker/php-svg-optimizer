#!/usr/bin/env bash

set -euo pipefail

usage() {
    echo "Usage: $0 [--skip-mutation]"
}

skip_mutation="${SKIP_MUTATION:-0}"

for argument in "$@"; do
    case "$argument" in
        --skip-mutation) skip_mutation=1 ;;
        -h | --help) usage; exit 0 ;;
        *) usage >&2; exit 2 ;;
    esac
done

cd "$(dirname "${BASH_SOURCE[0]}")/../.."

php="${PHP_BINARY:-php}"
artifacts=dev/artifacts
work_dir=$(mktemp -d)
trap 'rm -rf "$work_dir"' EXIT

native_path() {
    if command -v cygpath > /dev/null; then
        cygpath --mixed "$1"
    else
        echo "$1"
    fi
}

color_for() {
    local percent=$1

    if (( percent >= 80 )); then
        echo green
    elif (( percent >= 50 )); then
        echo yellow
    else
        echo red
    fi
}

badge() {
    local file=$1 label=$2 message=$3 color=$4

    curl -fsS --retry 3 -o "$artifacts/$file" \
        "https://img.shields.io/badge/${label}-${message}-${color}?style=flat"
    message=${message//%25/%}
    echo "$file: ${message//%20/ }"
}

percent_badge() {
    local file=$1 label=$2 percent=$3

    badge "$file" "$label" "${percent}%25" "$(color_for "$percent")"
}

package_size_badge() {
    local package_dir="$work_dir/package" tree size_kib

    tree=$(git stash create)
    mkdir "$package_dir"
    git archive --format=tar "${tree:-HEAD}" | tar -x -C "$package_dir"
    size_kib=$(find "$package_dir" -type f -exec cat {} + | wc -c | awk '{ printf "%.0f", $1 / 1024 }')

    badge package-size.svg size "${size_kib}%20KiB" brightgreen
}

test_badges() {
    local clover junit results coverage tests assertions

    clover=$(native_path "$work_dir/clover.xml")
    junit=$(native_path "$work_dir/junit.xml")

    XDEBUG_MODE=coverage "$php" vendor/bin/phpunit --colors=never --no-progress \
        --coverage-clover="$clover" --log-junit="$junit"

    results=$("$php" -r '
        $metrics = simplexml_load_file($argv[1])->project->metrics;
        $suite = simplexml_load_file($argv[2])->testsuite;
        printf(
            "%d %d %d",
            round(100 * (int) $metrics["coveredstatements"] / max(1, (int) $metrics["statements"])),
            (int) $suite["tests"],
            (int) $suite["assertions"],
        );
    ' "$clover" "$junit")

    read -r coverage tests assertions <<< "$results"

    percent_badge coverage.svg code_coverage "$coverage"
    badge tests.svg tests "$tests" blue
    badge assertions.svg assertions "$assertions" purple
}

type_coverage_badge() {
    local config report percent

    config=$(native_path "$work_dir/type-coverage.neon")
    report=$(native_path "$work_dir/type-coverage.json")

    cat > "$work_dir/type-coverage.neon" <<NEON
includes:
    - $(native_path "$PWD/phpstan.neon")

parameters:
    type_coverage:
        return: 0
        param: 0
        property: 0
        constant: 0
        measure: true
NEON

    "$php" vendor/bin/phpstan analyse -c "$config" --no-progress --error-format=json > "$report" 2> /dev/null || true

    percent=$("$php" -r '
        $report = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
        $messages = $report["errors"];
        foreach ($report["files"] as $file) {
            foreach ($file["messages"] as $message) {
                $messages[] = $message["message"];
            }
        }
        $filled = $possible = 0;
        foreach ($messages as $message) {
            if (preg_match("/type coverage is ([0-9.]+) % out of ([0-9]+) possible/", $message, $match) === 1) {
                $filled += (float) $match[1] / 100 * (int) $match[2];
                $possible += (int) $match[2];
            }
        }
        if ($possible === 0) {
            fwrite(STDERR, "No type coverage found in the PHPStan report.\n");
            exit(1);
        }
        echo round(100 * $filled / $possible);
    ' "$report")

    percent_badge type-coverage.svg type_coverage "$percent"
}

mutation_badges() {
    local summary results score coverage

    summary=$(native_path "$work_dir/infection-summary.json")

    "$php" vendor/bin/infection --threads=max --no-interaction --no-progress \
        --logger-summary-json="$summary" > /dev/null || true

    results=$("$php" -r '
        $stats = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR)["stats"];
        printf("%d %d", round($stats["msi"]), round($stats["mutationCodeCoverage"]));
    ' "$summary")

    read -r score coverage <<< "$results"

    percent_badge mutation-score.svg mutation_score "$score"
    percent_badge mutation-coverage.svg mutation_coverage "$coverage"
}

mkdir -p "$artifacts"

package_size_badge
test_badges
type_coverage_badge

if (( skip_mutation )); then
    echo "Mutation testing skipped; the mutation badges are unchanged."
else
    mutation_badges
fi

"$php" bin/svg-optimizer --quiet "$artifacts"
