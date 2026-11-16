<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

$testKey = 'base64:'.base64_encode(random_bytes(32));
putenv('APP_KEY='.$testKey);
$_ENV['APP_KEY'] = $testKey;
$_SERVER['APP_KEY'] = $testKey;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Tests that start other processes (queue workers) need committed rows, not a rolled-back transaction.
pest()->extend(TestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Integration');
