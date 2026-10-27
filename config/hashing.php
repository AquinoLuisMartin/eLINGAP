<?php

return [
    'driver' => 'argon2id',

    'argon' => ['memory' => 19456, 'time' => 2, 'threads' => 1, 'verify' => true],

    'bcrypt' => [
        'rounds' => (int) env('BCRYPT_ROUNDS', 12),
        'verify' => true,
        'limit' => 72,
    ],

    'rehash_on_login' => true,
];
