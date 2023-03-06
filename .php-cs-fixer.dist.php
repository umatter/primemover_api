<?php

$config = new PhpCsFixer\Config();

return $config
    ->setRules([
        '@PhpCsFixer' => true,
        '@PHP81Migration' => true,
        'multiline_whitespace_before_semicolons' => false,
        'single_line_comment_style' => false,
        'phpdoc_no_empty_return' => false,
        'explicit_indirect_variable' => false,
        'concat_space' => ['spacing' => 'one'],
        'php_unit_test_class_requires_covers' => false,
        'global_namespace_import' => true,
        'ordered_imports' => true,
        'phpdoc_align' => true,
        'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
        'no_superfluous_phpdoc_tags' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->ignoreDotFiles(true)
            ->ignoreVCSIgnored(true)
            ->ignoreUnreadableDirs()
            ->exclude([
                '/',
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
