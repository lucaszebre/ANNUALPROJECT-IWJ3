<?php

use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;

it('lets the application origin upload to the bucket', function () {
    config(['app.url' => 'http://localhost:8000/', 'filesystems.disks.s3.bucket' => 'app']);

    $client = Mockery::mock(S3Client::class);
    $client->shouldReceive('putBucketCors')->once()->with(Mockery::on(fn (array $args) => $args['Bucket'] === 'app'
        && $args['CORSConfiguration']['CORSRules'][0]['AllowedOrigins'] === ['http://localhost:8000']
        && in_array('PUT', $args['CORSConfiguration']['CORSRules'][0]['AllowedMethods'], true)));

    $disk = Mockery::mock(AwsS3V3Adapter::class);
    $disk->shouldReceive('getClient')->andReturn($client);
    Storage::set('s3', $disk);

    $this->artisan('storage:cors')
        ->expectsOutput('CORS rules set for http://localhost:8000.')
        ->assertSuccessful();
});

it('fails clearly when the storage cannot be reached', function () {
    $client = Mockery::mock(S3Client::class);
    $client->shouldReceive('putBucketCors')->andThrow(new RuntimeException('connection refused'));

    $disk = Mockery::mock(AwsS3V3Adapter::class);
    $disk->shouldReceive('getClient')->andReturn($client);
    Storage::set('s3', $disk);

    $this->artisan('storage:cors')
        ->expectsOutput('Could not set the CORS rules: connection refused')
        ->assertFailed();
});
