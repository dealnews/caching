<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'       => true,
        '@PhpCsFixer'  => true,
        '@Symfony'     => false,
        'array_syntax' => [
            'syntax' => 'short'
        ],
        'braces_position' => [
            'classes_opening_brace' => 'same_line',
            'functions_opening_brace' => 'same_line',
        ],
        'constant_case' => [
            'case' => 'lower'
        ],
        'lowercase_keywords' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'return',
                'use',
            ]
        ],
        'no_blank_lines_after_class_opening' => false,
    ])
    ->setFinder($finder)
;
