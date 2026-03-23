<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\TestLead;

beforeEach(function () {
    $this->fake = SObjects::fake();
});

it('creates a lead and gets an Id back', function () {
    $lead = TestLead::create([
        'FirstName' => 'Test',
        'LastName' => 'User',
        'Email' => 'test@test.com',
        'Company' => 'Acme',
    ]);

    expect($lead->Id)->not->toBeNull()
        ->and($lead->exists)->toBeTrue()
        ->and($lead->wasRecentlyCreated)->toBeTrue();

    $this->fake->assertAuthenticated();
    $this->fake->assertSobjectsCalled('Lead', 'post');
});

it('fires creating event and observer sets Company', function () {
    $lead = TestLead::create([
        'FirstName' => 'Test',
        'LastName' => 'User',
        'Email' => 'test@test.com',
    ]);

    // TestObserver sets Company to 'Test Company' on creating
    expect($lead->Company)->toBe('Test Company');
});

it('fires booted closure and sets Phone on creating', function () {
    $lead = TestLead::create([
        'FirstName' => 'Test',
        'LastName' => 'User',
        'Email' => 'test@test.com',
    ]);

    expect($lead->Phone)->toBe('1231231234');
});

it('updates a lead', function () {
    $lead = new TestLead([
        'Id' => '00Q000000000001AAA',
        'FirstName' => 'Test',
        'LastName' => 'User',
        'Email' => 'test@test.com',
        'Company' => 'Acme',
    ]);
    $lead->exists = true;
    $lead->syncOriginal();

    $lead->FirstName = 'Updated';
    $result = $lead->save();

    expect($result)->toBeTrue();
    $this->fake->assertSobjectsCalled('Lead/00Q000000000001AAA', 'patch');
});

it('deletes a lead', function () {
    $lead = new TestLead([
        'Id' => '00Q000000000001AAA',
        'FirstName' => 'Test',
        'LastName' => 'User',
        'Company' => 'Acme',
    ]);

    $result = $lead->delete();

    expect($result)->toBeTrue();
    $this->fake->assertSobjectsCalled('Lead/00Q000000000001AAA', 'delete');
});

it('tracks dirty state and changes', function () {
    $lead = new TestLead([
        'Id' => '00Q000000000001AAA',
        'FirstName' => 'Test',
        'LastName' => 'User',
        'Company' => 'Acme',
    ]);
    $lead->syncOriginal();

    expect($lead->isDirty())->toBeFalse();

    $lead->FirstName = 'Updated';

    expect($lead->isDirty())->toBeTrue()
        ->and($lead->isDirty('FirstName'))->toBeTrue()
        ->and($lead->getDirty())->toHaveKey('FirstName');
});

it('returns writeable attributes excluding readonly fields', function () {
    $lead = new TestLead([
        'Id' => '00Q000000000001AAA',
        'FirstName' => 'Test',
        'LastName' => 'User',
        'IsDeleted' => false,
        'CreatedDate' => '2024-01-01',
    ]);

    $writeable = $lead->writeableAttributes(['IsDeleted', 'CreatedDate']);

    expect($writeable)->not->toHaveKey('IsDeleted')
        ->and($writeable)->not->toHaveKey('CreatedDate')
        ->and($writeable)->toHaveKey('FirstName');
});
