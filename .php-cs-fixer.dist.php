<?php

/**
 *     This file is part of the php-svg-optimizer package.
 *     (c) Mathias Reker <github@reker.dk>
 *     For the full copyright and license information, please view the LICENSE
 *     file that was distributed with this source code.
 */

declare(strict_types=1);

$header = <<<'EOD'
        This file is part of the php-svg-optimizer package.
        (c) Mathias Reker <github@reker.dk>
        For the full copyright and license information, please view the LICENSE
        file that was distributed with this source code.
    EOD;

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->in([__DIR__])
;

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setRules([
        // Header
        'header_comment' => [
            'header' => $header,
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'bottom',
        ],

        // Migration Rules
        '@PHP83Migration' => true,
        '@PHP82Migration:risky' => true,
        '@PHPUnit100Migration:risky' => true,

        // Doctrine Rules
        '@DoctrineAnnotation' => true,

        // In no presets
        'attribute_empty_parentheses' => true,
        'heredoc_closing_marker' => true,
        'multiline_string_to_heredoc' => true,
        'numeric_literal_separator' => true,
        'ordered_attributes' => true,
        'php_unit_attributes' => true,
        'return_to_yield_from' => true,
        'phpdoc_tag_casing' => true,
        'phpdoc_param_order' => true,
        'mb_str_functions' => true,
        'date_time_immutable' => true,
        'ordered_interfaces' => true,
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['expectedDeprecation'],
        ],

        // PHP-CS-Fixer Rules
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        // Symfony Rules
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setFinder($finder)
    ->setLineEnding(\PHP_EOL)
;

return $config;
