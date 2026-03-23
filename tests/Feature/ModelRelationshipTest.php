<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\TestLead;
use Lester\EloquentSalesForce\TestTask;

beforeEach(function () {
    $this->fake = SObjects::fake();
});

it('defines hasMany relationship to tasks', function () {
    $lead = new TestLead(['Id' => '00Q000000000001AAA']);

    $relation = $lead->tasks();

    expect($relation)->toBeInstanceOf(\Lester\EloquentSalesForce\Database\SOQLHasMany::class);
});

it('loads tasks through hasMany', function () {
    $this->fake->withQueryResponse('Task', [
        'totalSize' => 2,
        'done' => true,
        'records' => [
            ['Id' => 'T01', 'WhoId' => '00Q000000000001AAA', 'Subject' => 'Call', 'attributes' => ['type' => 'Task']],
            ['Id' => 'T02', 'WhoId' => '00Q000000000001AAA', 'Subject' => 'Email', 'attributes' => ['type' => 'Task']],
        ],
    ]);

    $lead = new TestLead(['Id' => '00Q000000000001AAA']);
    $tasks = $lead->tasks()->get();

    expect($tasks)->toHaveCount(2)
        ->and($tasks->first()->Subject)->toBe('Call');
});

it('defines belongsTo relationship from task to lead', function () {
    $task = new TestTask([
        'Id' => 'T01',
        'WhoId' => '00Q000000000001AAA',
    ]);

    $relation = $task->lead();

    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});

it('compiles join as SOQL subquery', function () {
    // Configure layout response for Task so objectFields(['*']) works
    $this->fake->withDescribeResponse('Task/describe/compactLayouts/primary/', [
        'fieldItems' => [
            [
                'layoutComponents' => [
                    ['details' => ['name' => 'Subject', 'updateable' => true]],
                ],
            ],
        ],
    ]);

    $sql = TestLead::query()
        ->join('Task', 'Task.WhoId', '=', 'Lead.Id')
        ->toSql();

    expect($sql)->toContain('select')
        ->and($sql)->toContain('from Lead')
        ->and($sql)->toContain('Tasks');
});
