<?php

$rules = [
    '@PhpCsFixer' => true,
    '@PHP81Migration' => true,
    'concat_space' => ['spacing' => 'one'],
    'multiline_whitespace_before_semicolons' => false,
    'single_line_comment_style' => false,
    'phpdoc_no_empty_return' => false,
    'explicit_indirect_variable' => false,
    'no_superfluous_phpdoc_tags' => false,
    'php_unit_test_class_requires_covers' => false,
    'phpdoc_order' => ['order' => ['param', 'throws', 'return']],
    'phpdoc_types_order' => ['null_adjustment' => 'always_last'],
    'global_namespace_import' => true,
    'ordered_imports' => true,
    'phpdoc_align' => true,
    'octal_notation' => false,
    'fully_qualified_strict_types' => false,
];

$paths = [
    dirname(__DIR__, 4).'/app',
    dirname(__DIR__, 4).'/config',
    dirname(__DIR__, 4).'/database',
    dirname(__DIR__, 4).'/resources',
    dirname(__DIR__, 4).'/routes',
    dirname(__DIR__, 4).'/tests',
];
$finder = PhpCsFixer\Finder::create();
foreach ($paths as $path) {
    try {
        $finder->in($path);
    } catch (Exception $e) {
    }
}
$finder->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$config = new PhpCsFixer\Config();

return $config
    ->setFinder($finder)
    ->setRules($rules);
