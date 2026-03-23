<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\TestLead;

beforeEach(function () {
    $this->fake = SObjects::fake();
});

it('queries leads with where clause', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 1,
        'done' => true,
        'records' => [
            [
                'Id' => '00Q000000000001AAA',
                'FirstName' => 'Test',
                'LastName' => 'User',
                'Email' => 'test@test.com',
                'Company' => 'Acme',
                'attributes' => ['type' => 'Lead'],
            ],
        ],
    ]);

    $leads = TestLead::where('Email', 'test@test.com')->get();

    expect($leads)->toHaveCount(1)
        ->and($leads->first()->Email)->toBe('test@test.com');
});

it('queries with whereIn', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 2,
        'done' => true,
        'records' => [
            ['Id' => '001', 'attributes' => ['type' => 'Lead']],
            ['Id' => '002', 'attributes' => ['type' => 'Lead']],
        ],
    ]);

    $leads = TestLead::whereIn('Id', ['001', '002'])->get();

    expect($leads)->toHaveCount(2);
});

it('queries with orWhere', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 2,
        'done' => true,
        'records' => [
            ['Id' => '001', 'Email' => 'a@test.com', 'attributes' => ['type' => 'Lead']],
            ['Id' => '002', 'Email' => 'b@test.com', 'attributes' => ['type' => 'Lead']],
        ],
    ]);

    $leads = TestLead::where('Email', 'a@test.com')
        ->orWhere('Email', 'b@test.com')
        ->get();

    expect($leads)->toHaveCount(2);
});

it('returns empty collection when no results', function () {
    $leads = TestLead::where('Email', 'nonexistent@test.com')->get();

    expect($leads)->toBeEmpty();
});

it('queries with limit', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 1,
        'done' => true,
        'records' => [
            ['Id' => '001', 'attributes' => ['type' => 'Lead']],
        ],
    ]);

    $leads = TestLead::limit(5)->get();

    expect($leads)->toHaveCount(1);
    $this->fake->assertQueried('limit 5');
});

it('paginates results', function () {
    $this->fake->withQueryResponse('*', function ($soql) {
        if (str_contains($soql, 'count(')) {
            return [
                'totalSize' => 1,
                'done' => true,
                'records' => [['aggregate' => 3]],
            ];
        }

        return [
            'totalSize' => 2,
            'done' => true,
            'records' => [
                ['Id' => '001', 'Email' => 'a@test.com', 'attributes' => ['type' => 'Lead']],
                ['Id' => '002', 'Email' => 'b@test.com', 'attributes' => ['type' => 'Lead']],
            ],
        ];
    });

    // Use explicit columns to avoid ServiceProvider::objectFields fetching layout
    $result = TestLead::paginate(2, ['Id', 'Email']);

    expect($result)->toHaveCount(2)
        ->and($result->total())->toBe(3);
});

it('uses cursor for lazy iteration', function () {
    $this->fake->withQueryResponse('Lead', [
        'totalSize' => 2,
        'done' => true,
        'records' => [
            ['Id' => '001', 'FirstName' => 'A', 'attributes' => ['type' => 'Lead']],
            ['Id' => '002', 'FirstName' => 'B', 'attributes' => ['type' => 'Lead']],
        ],
    ]);

    $count = 0;
    foreach (TestLead::cursor() as $lead) {
        $count++;
    }

    expect($count)->toBe(2);
});
