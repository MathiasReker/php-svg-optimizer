<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

use MathiasReker\PhpSvgOptimizer\Rule;
use MathiasReker\PhpSvgOptimizer\RuleSet;

require __DIR__ . '/../../vendor/autoload.php';

$schemaPath = __DIR__ . '/../../resources/svg-optimizer.schema.json';

$ruleProperties = [];

foreach (Rule::cases() as $rule) {
    $ruleProperties[$rule->value] = [
        'type' => 'boolean',
        'description' => $rule->getDescription() . ($rule->isRisky() ? ' Risky: only runs when allowRisky is true.' : ''),
        'x-risky' => $rule->isRisky(),
    ];
}

$schema = [
    '$schema' => 'http://json-schema.org/draft-07/schema#',
    '$id' => 'https://raw.githubusercontent.com/MathiasReker/php-svg-optimizer/develop/resources/svg-optimizer.schema.json',
    'title' => 'svg-optimizer configuration',
    'type' => 'object',
    'additionalProperties' => false,
    'properties' => [
        '$schema' => [
            'type' => 'string',
            'description' => 'The schema of this file, which editors use for completion and validation.',
        ],
        'sets' => [
            'type' => 'array',
            'description' => 'The rule sets to select. "default" selects the standards, cleanup, conversion, structure and minification sets. Without sets and rules the default selection is used.',
            'uniqueItems' => true,
            'items' => [
                'enum' => ['default', ...array_column(RuleSet::cases(), 'value')],
            ],
        ],
        'rules' => [
            'type' => 'object',
            'description' => 'true selects a rule, false leaves it out even if a set selects it.',
            'additionalProperties' => false,
            'properties' => $ruleProperties,
        ],
        'allowRisky' => [
            'type' => 'boolean',
            'description' => 'Allow the risky rules that sets or rules select. They may change how the SVG renders or is used.',
            'default' => false,
        ],
    ],
];

$json = json_encode($schema, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR) . "\n";

if (in_array('--check', $argv, true)) {
    if (!is_file($schemaPath) || file_get_contents($schemaPath) !== $json) {
        fwrite(\STDERR, 'resources/svg-optimizer.schema.json is out of date. Run "composer build:schema".' . \PHP_EOL);

        exit(1);
    }

    exit(0);
}

if (!is_dir(dirname($schemaPath))) {
    mkdir(dirname($schemaPath), 0o755, true);
}

file_put_contents($schemaPath, $json);
echo 'resources/svg-optimizer.schema.json', \PHP_EOL;
