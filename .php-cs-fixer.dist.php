<?php

declare(strict_types=1);

$header = <<<'EOF'
    This file is part of the php-svg-optimizer package.
    (c) Mathias Reker <github@reker.dk>
    For the full copyright and license information, please view the LICENSE
    file that was distributed with this source code.
    EOF;

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->in([__DIR__]);

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        // Header
        'header_comment' => [
            'header' => $header,
            'comment_type' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'bottom',
        ],

        // Migration Rules
        '@PHP82Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PHPUnit100Migration:risky' => true,

        // Symfony Rules
        '@Symfony' => true,
        '@Symfony:risky' => true,

        // Code Style
        'array_indentation' => true,
        'concat_space' => ['spacing' => 'one'],
        'control_structure_braces' => true,
        'control_structure_continuation_position' => true,
        'method_chaining_indentation' => true,
        'multiline_comment_opening_closing' => true,
        'operator_linebreak' => true,
        'statement_indentation' => true,

        // PHPDoc
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['expectedDeprecation'],
        ],
        'phpdoc_var_annotation_correct_order' => true,

        // Best Practices
        'self_static_accessor' => true,
        'ordered_interfaces' => true,
        'return_assignment' => true,
        'no_useless_else' => true,
        'no_superfluous_elseif' => true,
        'no_useless_return' => true,
        'no_null_property_initialization' => true,
        'no_alias_functions' => true,
        'no_extra_blank_lines' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try'],
        ],

        // Strictness
        'strict_param' => true,
        'strict_comparison' => true,

        // Function Usage
        'use_arrow_functions' => true,
        'mb_str_functions' => true,
        'date_time_immutable' => true,

        // Naming Conventions
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
                'const' => 'one',
            ],
        ],
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,

        // Security
        'escape_implicit_backslashes' => true,
        'no_php4_constructor' => true,
        'no_unreachable_default_argument_value' => true,

        // Error Handling
        'error_suppression' => true,
        'no_blank_lines_after_phpdoc' => true,

        // Performance
        'combine_consecutive_unsets' => true,
        'simplified_null_return' => true,

        // Disabling rules that are not needed
        'psr_autoloading' => false,
    ])
    ->setFinder($finder)
    ->setLineEnding(PHP_EOL);

return $config;
