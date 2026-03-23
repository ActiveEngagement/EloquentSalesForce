<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\SalesForceObject;

beforeEach(function () {
    SObjects::fake();
});

it('constructs with attributes and sets table from type', function () {
    $obj = new SalesForceObject([
        'Id' => '001000000000001AAA',
        'Name' => 'Test Account',
        'attributes' => ['type' => 'Account'],
    ]);

    expect($obj->getTable())->toBe('Account')
        ->and($obj->Name)->toBe('Test Account')
        ->and($obj->exists)->toBeTrue();
});

it('sets exists to true when Id is present', function () {
    $obj = new SalesForceObject([
        'Id' => '001000000000001AAA',
    ]);

    expect($obj->exists)->toBeTrue();
});

it('sets exists to false when no Id', function () {
    $obj = new SalesForceObject([
        'Name' => 'Test',
    ]);

    expect($obj->Id)->toBeNull();
});

it('can set table dynamically', function () {
    $obj = new SalesForceObject([]);
    $obj->setTable('Contact');

    expect($obj->getTable())->toBe('Contact')
        ->and($obj->sf_attributes['type'])->toBe('Contact');
});

it('defaults table to class basename without attributes type', function () {
    $obj = new SalesForceObject([
        'Name' => 'Test',
    ]);

    expect($obj->getTable())->toBe('SalesForceObject');
});
