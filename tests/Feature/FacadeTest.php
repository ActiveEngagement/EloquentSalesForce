<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\SalesForceObject;

beforeEach(function () {
    $this->fake = SObjects::fake();
});

it('authenticates without errors', function () {
    SObjects::authenticate();

    $this->fake->assertAuthenticated();
});

it('returns versions', function () {
    $versions = SObjects::versions();

    expect($versions)->toBeArray()
        ->and($versions[0]['version'])->toBe('59.0');
});

it('returns instance url', function () {
    expect(SObjects::instanceUrl())->toBe('https://test.salesforce.com');
});

it('returns picklist values', function () {
    $this->fake->withPicklistValues('Lead', 'Status', [
        'Open' => 'Open - Not Contacted',
        'Working' => 'Working - Contacted',
        'Closed' => 'Closed - Converted',
    ]);

    $values = SObjects::getPicklistValues('Lead', 'Status');

    expect($values)->toHaveCount(3)
        ->and($values->get('Open'))->toBe('Open - Not Contacted');
});

it('describes an object', function () {
    $this->fake->withDescribeResponse('Lead', [
        'name' => 'Lead',
        'fields' => [
            ['name' => 'Id', 'type' => 'id'],
            ['name' => 'Email', 'type' => 'email'],
        ],
    ]);

    $result = SObjects::describe('Lead');

    expect($result)->toHaveKey('name')
        ->and($result['name'])->toBe('Lead')
        ->and($result['fields'])->toHaveCount(2);
});

it('describes an object with key', function () {
    $this->fake->withDescribeResponse('Lead', [
        'name' => 'Lead',
        'label' => 'Lead Object',
    ]);

    $result = SObjects::describe('Lead', 'label');

    expect($result)->toBe('Lead Object');
});

it('creates a SalesForceObject', function () {
    $obj = SObjects::object([
        'Id' => '001',
        'Name' => 'Test',
        'attributes' => ['type' => 'Account'],
    ]);

    expect($obj)->toBeInstanceOf(SalesForceObject::class)
        ->and($obj->getTable())->toBe('Account');
});

it('converts salesforce ids', function () {
    $id15 = '5003000000D8cuI';
    $id18 = SObjects::convert($id15);

    expect($id18)->toHaveLength(18)
        ->and(SObjects::isSalesForceId($id18))->toBeTrue();
});

it('returns empty picklist when not configured', function () {
    $values = SObjects::getPicklistValues('Lead', 'NonExistentField');

    expect($values)->toBeEmpty();
});
