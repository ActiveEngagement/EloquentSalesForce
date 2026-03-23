<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\SalesForceObject;
use Lester\EloquentSalesForce\Exceptions\UnableToLockRowException;
use Lester\EloquentSalesForce\Exceptions\MalformedQueryException;
use Lester\EloquentSalesForce\Exceptions\RequestLimitExceeded;
use Lester\EloquentSalesForce\Exceptions\RestAPIException;

beforeEach(function () {
    SObjects::fake();
});

it('converts a 15-char salesforce id to 18-char', function () {
    $result = SObjects::convert('5003000000D8cuI');

    expect($result)->toHaveLength(18)
        ->and($result)->toStartWith('5003000000D8cuI');
});

it('returns the same string if not 15 chars', function () {
    expect(SObjects::convert('abc'))->toBe('abc');
    expect(SObjects::convert('5003000000D8cuIAAR'))->toBe('5003000000D8cuIAAR');
});

it('detects valid salesforce ids', function () {
    expect(SObjects::isSalesForceId('5003000000D8cuI'))->toBeTrue();
    expect(SObjects::isSalesForceId('5003000000D8cuIAAR'))->toBeTrue();
    expect(SObjects::isSalesForceId('abc'))->toBeFalse();
    expect(SObjects::isSalesForceId(''))->toBeFalse();
    expect(SObjects::isSalesForceId('has spaces in it'))->toBeFalse();
});

it('creates a SalesForceObject from attributes', function () {
    $obj = SObjects::object([
        'Id' => '001000000000001AAA',
        'Name' => 'Test',
        'attributes' => ['type' => 'Account'],
    ]);

    expect($obj)->toBeInstanceOf(SalesForceObject::class)
        ->and($obj->Name)->toBe('Test')
        ->and($obj->getTable())->toBe('Account');
});

it('processes UNABLE_TO_LOCK_ROW exception', function () {
    SObjects::processExceptions([
        (object) ['errorCode' => 'UNABLE_TO_LOCK_ROW', 'message' => 'locked'],
    ]);
})->throws(UnableToLockRowException::class);

it('processes MALFORMED_QUERY exception', function () {
    SObjects::processExceptions([
        (object) ['errorCode' => 'MALFORMED_QUERY', 'message' => 'bad query'],
    ]);
})->throws(MalformedQueryException::class);

it('processes REQUEST_LIMIT_EXCEEDED exception', function () {
    SObjects::processExceptions([
        (object) ['errorCode' => 'REQUEST_LIMIT_EXCEEDED', 'message' => 'too many'],
    ]);
})->throws(RequestLimitExceeded::class);

it('processes unknown exception as RestAPIException', function () {
    SObjects::processExceptions([
        (object) ['errorCode' => 'UNKNOWN_ERROR', 'message' => 'something'],
    ]);
})->throws(RestAPIException::class);

it('returns query history as a collection', function () {
    expect(SObjects::queryHistory())->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and(SObjects::queryHistory())->toBeEmpty();
});

it('tracks queries in history', function () {
    SObjects::query('SELECT Id FROM Lead');

    expect(SObjects::queryHistory())->toHaveCount(1);
});
