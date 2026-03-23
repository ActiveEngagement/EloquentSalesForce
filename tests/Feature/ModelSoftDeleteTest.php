<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\TestLead;

beforeEach(function () {
    $this->fake = SObjects::fake();
});

it('reports trashed status from IsDeleted', function () {
    $lead = new TestLead(['Id' => '001', 'IsDeleted' => true]);
    expect($lead->trashed())->toBeTrue();

    $lead2 = new TestLead(['Id' => '002', 'IsDeleted' => false]);
    expect($lead2->trashed())->toBeFalse();
});

it('reports not trashed when IsDeleted is not set', function () {
    $lead = new TestLead(['Id' => '001']);
    expect($lead->trashed())->toBeFalse();
});

it('throws exception on restore', function () {
    $lead = new TestLead(['Id' => '001']);
    $lead->restore();
})->throws(\Exception::class, 'The SalesForce Rest API does not natively support UNDELETE');

it('queries withTrashed using queryAll', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 1,
        'done' => true,
        'records' => [
            ['Id' => '001', 'IsDeleted' => true, 'attributes' => ['type' => 'Lead']],
        ],
    ]);

    $leads = TestLead::withTrashed()->get();

    expect($leads)->toHaveCount(1);
});

it('queries onlyTrashed with IsDeleted filter', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 1,
        'done' => true,
        'records' => [
            ['Id' => '001', 'IsDeleted' => true, 'attributes' => ['type' => 'Lead']],
        ],
    ]);

    $sql = TestLead::onlyTrashed()->toSql();

    // Boolean bindings resolved at execution time; toSql shows 1
    expect($sql)->toContain('IsDeleted = 1');
});
