<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

$testKey = 'base64:'.base64_encode(random_bytes(32));
putenv('APP_KEY='.$testKey);
$_ENV['APP_KEY'] = $testKey;
$_SERVER['APP_KEY'] = $testKey;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
