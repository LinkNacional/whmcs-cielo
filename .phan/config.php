<?php

return [
    'target_php_version' => '8.1',
    'minimum_target_php_version' => '7.4',
    'backward_compatibility_checks' => true,
    'color_issue_messages_if_supported' => true,
    'scalar_implicit_cast' => true,
    'disable_suggestions' => true,
    'read_type_annotations' => false,
    'analyzed_file_extensions' => ['php'],
    'suppress_issue_types' => [
        // 'PhanUndeclaredClassMethod',
        // 'PhanUndeclaredFunction',
        'PhanTypeMismatchReturn',
        'PhanTypeMismatchReturnNullable',
        'PhanTypeMismatchReturnProbablyReal',
        'PhanTypeMismatchReturnReal',
        'PhanUseNormalNoEffect',
        'PhanTypeMismatchArgument',
        'PhanTypeArraySuspiciousNullable',
        'PhanTypeArraySuspiciousNull',
        'PhanTypePossiblyInvalidDimOffset',
        'PhanTypeMismatchDimFetch',
        'PhanTypeArraySuspicious'
    ],

    'directory_list' => [
        'src',
        '.phan/stubs'
    ],

    'exclude_analysis_directory_list' => [
        'vendor/',
        '.phan/stubs/'
    ],

    'exclude_file_list' => [
        'src/modules/gateways/lknc_cielo_credit_card/helpers/license_functions.php',
    ]
];
