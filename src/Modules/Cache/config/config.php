<?php

return [
    'name' => 'Cache',
    'member_role_cache_key' => '%s_%s_member_role_cache_key',
    'member_data_cache_key' => '%s_%s_member_data_cache_key',
    'user_data_cache_key' => '%s_%s_user_data_cache_key',
    'ng_word_cache_key' => '%s_prohibited_word_cache_key',

    'cache_expiration_default' => 3600,
    'user_data_cache_expiration' => 1800,
    'member_data_cache_expiration_min' => 2600000, // about 1 month in seconds
    'member_data_cache_expiration_max' => 5200000, // about 2 months in seconds
    'ng_word_expiration' => 2600000, // about 1 month in seconds
];
