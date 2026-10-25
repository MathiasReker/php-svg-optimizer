<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\SvgOptimizer;
use Random\Engine\Mt19937;
use Random\Randomizer;

require __DIR__ . '/../../vendor/autoload.php';

$options = getopt('', ['iterations:', 'save:', 'compare:']);
$iterations = max(1, (int) ($options['iterations'] ?? 5));

/**
 * @return array<string, string>
 */
function generatedDocuments(): array
{
    $randomizer = new Randomizer(new Mt19937(8_800));

    $icons = '';
    for ($i = 0; $i < 400; ++$i) {
        $icons .= sprintf(
            '<symbol id="icon-%1$d" viewBox="0 0 24 24"><path d="M%2$d.500 %3$d.250 L %4$d.000 %5$d.750 C %2$d %3$d, %4$d %5$d, 12.0000 12.0000 Z" fill="#FF0000" stroke="rgb(0, 0, 255)" stroke-width="1.0px"/></symbol>',
            $i,
            $randomizer->getInt(0, 23),
            $randomizer->getInt(0, 23),
            $randomizer->getInt(0, 23),
            $randomizer->getInt(0, 23),
        );
    }

    $editor = '<defs>';
    for ($i = 0; $i < 150; ++$i) {
        $editor .= sprintf('<linearGradient id="g%d" x1="0%%" y1="0%%" x2="100%%" y2="0%%"><stop offset="0" stop-color="#%06x"/><stop offset="1" stop-color="#%06x" stop-opacity="1.0"/></linearGradient>', $i, $randomizer->getInt(0, 0xFF_FF_FF), $randomizer->getInt(0, 0xFF_FF_FF));
    }

    $editor .= '</defs><style type="text/css">.a{fill:#336699;stroke:none}.b{opacity:0.50}</style>';
    for ($i = 0; $i < 600; ++$i) {
        $editor .= sprintf(
            '<g id="layer%1$d" inkscape:label="Layer %1$d" transform="translate(%2$d.0, %3$d.0)"><g><rect class="a" x="0.0" y="0.0" width="%4$d" height="%5$d" rx="0" style="fill-opacity:1.0;stroke-width:1px"/><ellipse cx="10" cy="10" rx="5" ry="5" fill="url(#g%6$d)"/><polygon points="0,0 10,0 10,10 0,10"/></g><!-- layer %1$d --></g>',
            $i,
            $randomizer->getInt(0, 900),
            $randomizer->getInt(0, 900),
            $randomizer->getInt(1, 50),
            $randomizer->getInt(1, 50),
            $randomizer->getInt(0, 149),
        );
    }

    $text = '';
    for ($i = 0; $i < 300; ++$i) {
        $text .= sprintf("<text x=\"%d\" y=\"%d\" font-family=\"Arial, sans-serif\">\n      Line %d of the\n      benchmark text\n    </text>\n", $randomizer->getInt(0, 500), $i * 12, $i);
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape" width="1000" height="1000" viewBox="0 0 1000 1000">';

    return [
        'generated/icon-sheet' => $svg . $icons . '</svg>',
        'generated/editor-export' => $svg . $editor . '</svg>',
        'generated/text' => $svg . $text . '</svg>',
    ];
}

$documents = [];
foreach (glob(__DIR__ . '/../samples/*.svg') ?: [] as $file) {
    $documents['samples/' . basename($file)] = (string) file_get_contents($file);
}

$documents += generatedDocuments();

$allRules = (new SvgOptimizer())->withRules(...Rule::cases());
$optimizers = [
    'safe' => $allRules->withoutRules(...array_filter(Rule::cases(), static fn (Rule $rule): bool => $rule->isRisky())),
    'risky' => $allRules->withRiskyRulesAllowed(),
];
$results = [];
$checksums = [];
$totalSeconds = 0.0;

foreach ($documents as $name => $svg) {
    foreach (array_keys($optimizers) as $mode) {
        $timings = [];
        $output = '';

        for ($run = 0; $run < $iterations; ++$run) {
            $start = hrtime(true);
            $output = $optimizers[$mode]->optimizeString($svg)->getContent();
            $timings[] = (hrtime(true) - $start) / 1e9;
        }

        sort($timings);
        $median = $timings[intdiv(count($timings), 2)];
        $totalSeconds += $median;
        $checksums[$name . ' ' . $mode] = hash('sha256', $output);
        $results[] = [$name, $mode, mb_strlen($svg, '8bit'), mb_strlen($output, '8bit'), $median];
    }
}

printf("%-34s %-6s %10s %10s %10s\n", 'document', 'mode', 'in (B)', 'out (B)', 'median ms');
foreach ($results as [$name, $mode, $in, $out, $median]) {
    printf("%-34s %-6s %10d %10d %10.2f\n", $name, $mode, $in, $out, $median * 1_000);
}

printf("\nTotal of medians: %.2f ms (%d iterations, PHP %s)\n", $totalSeconds * 1_000, $iterations, \PHP_VERSION);

if (array_key_exists('save', $options)) {
    file_put_contents((string) $options['save'], json_encode($checksums, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
    printf("Saved %d output checksums to %s\n", count($checksums), $options['save']);
}

if (array_key_exists('compare', $options)) {
    /** @var array<string, string> $expected */
    $expected = json_decode((string) file_get_contents((string) $options['compare']), true, 512, \JSON_THROW_ON_ERROR);
    $different = array_keys(array_diff_assoc($checksums, $expected) + array_diff_key($expected, $checksums));

    if ([] !== $different) {
        printf("Output differs from %s for: %s\n", $options['compare'], implode(', ', $different));

        exit(1);
    }

    printf("Output is identical to %s for all %d runs\n", $options['compare'], count($checksums));
}
