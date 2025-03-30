<?php

return [
    'model_paths' => [
        'app' . DIRECTORY_SEPARATOR . 'Models'
    ],
    'auto_infer_migrations' => true,
    'implications' => [
        'table' => true,
        'column' => true,
        'index' => true,
        'unique' => true,
        'primary' => true,
        'foreign_key' => true,
        'relationship' => true,
        'pivot_table' => true,
        'pivot_column' => true,
        'off' => true,
    ],
];
