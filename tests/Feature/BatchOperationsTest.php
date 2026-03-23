<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\TestLead;

beforeEach(function () {
    $this->fake = SObjects::fake();
});

it('bulk inserts a collection of leads', function () {
    $leads = collect([
        new TestLead(['FirstName' => 'A', 'LastName' => 'User', 'Company' => 'Co', 'Email' => 'a@test.com']),
        new TestLead(['FirstName' => 'B', 'LastName' => 'User', 'Company' => 'Co', 'Email' => 'b@test.com']),
        new TestLead(['FirstName' => 'C', 'LastName' => 'User', 'Company' => 'Co', 'Email' => 'c@test.com']),
    ]);

    $result = TestLead::query()->insert($leads);

    expect($result)->toHaveCount(3)
        ->and($result->first()->Id)->not->toBeNull();
});

it('bulk deletes via builder', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 2,
        'done' => true,
        'records' => [
            ['Id' => '001', 'attributes' => ['type' => 'Lead']],
            ['Id' => '002', 'attributes' => ['type' => 'Lead']],
        ],
    ]);

    TestLead::where('Company', 'DeleteMe')->delete();

    $this->fake->assertCompositeCalled('sobjects', 'delete');
});

it('truncates records through delete', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 1,
        'done' => true,
        'records' => [
            ['Id' => '001', 'attributes' => ['type' => 'Lead']],
        ],
    ]);

    TestLead::query()->truncate();

    $this->fake->assertCompositeCalled('sobjects', 'delete');
});

it('performs mass update on a collection', function () {
    $leads = collect([
        new TestLead(['Id' => '001', 'FirstName' => 'Updated A', 'attributes' => ['type' => 'Lead']]),
        new TestLead(['Id' => '002', 'FirstName' => 'Updated B', 'attributes' => ['type' => 'Lead']]),
    ]);

    // Mark them as dirty
    foreach ($leads as $lead) {
        $lead->syncOriginal();
        $lead->FirstName = $lead->FirstName . ' Modified';
    }

    SObjects::update($leads);

    $this->fake->assertMassUpdate($leads);
});
