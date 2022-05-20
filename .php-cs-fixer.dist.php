<?php

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PSR2' => true,
        '@PSR12' => true,
        '@Symfony' => true,
        '@PhpCsFixer' => true,
        'multiline_whitespace_before_semicolons' => false,
        'single_line_comment_style' => false,
        'phpdoc_no_empty_return' => false,
        'explicit_indirect_variable' => false,
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude([
                'bootstrap',
                'docker',
                'node_modules',
                'html',
                'public',
                'resources',
                'scripts',
                'storage',
                'vendor',
            ])
            ->in(__DIR__)
    );
