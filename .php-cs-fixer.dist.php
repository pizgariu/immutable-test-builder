<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$directories = array_values(array_filter(
    [__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/benchmark', __DIR__ . '/config'],
    'is_dir',
));

$finder = Finder::create()
    ->in($directories)
    ->exclude('PHPStan/data')
    ->append([__FILE__])
;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS2.0:risky' => true,
        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'final_class' => true,
        'function_declaration' => ['closure_fn_spacing' => 'one'],
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'new_line_for_chained_calls'],
        'no_unused_imports' => true,
        'native_function_invocation' => false,
        'ordered_class_elements' => false,
    ])
;
