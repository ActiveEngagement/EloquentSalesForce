<?php

use Lester\EloquentSalesForce\Facades\SObjects;
use Lester\EloquentSalesForce\TestLead;

beforeEach(function () {
    SObjects::fake();
});

it('compiles a basic select query', function () {
    $sql = TestLead::query()->toSql();

    expect($sql)->toContain('select')
        ->and($sql)->toContain('from Lead')
        ->and($sql)->toContain('Email')
        ->and($sql)->toContain('FirstName');
});

it('compiles a select with specific columns', function () {
    $sql = TestLead::select('Id', 'Email')->toSql();

    expect($sql)->toContain('Id')
        ->and($sql)->toContain('Email')
        ->and($sql)->toContain('from Lead');
});

it('compiles a where clause', function () {
    $sql = TestLead::where('Email', 'test@test.com')->toSql();

    expect($sql)->toContain("Email = 'test@test.com'");
});

it('compiles not like operator', function () {
    $sql = TestLead::where('FirstName', 'not like', '%test%')->toSql();

    expect($sql)->toContain('(not FirstName like');
});

it('compiles where boolean', function () {
    $sql = TestLead::where('DoNotCall', true)->toSql();

    // Boolean bindings are resolved at execution time by SOQLConnection::prepareBindings
    // At toSql level they appear as 1
    expect($sql)->toContain('DoNotCall = 1');
});

it('compiles whereIn', function () {
    $sql = TestLead::whereIn('Id', ['001', '002', '003'])->toSql();

    expect($sql)->toContain("in ('001', '002', '003')");
});

it('compiles whereNull', function () {
    $sql = TestLead::whereNull('Email')->toSql();

    expect($sql)->toContain('Email = NULL');
});

it('compiles whereNotNull', function () {
    $sql = TestLead::whereNotNull('Email')->toSql();

    expect($sql)->toContain('Email <> NULL');
});

it('compiles limit', function () {
    $sql = TestLead::limit(10)->toSql();

    expect($sql)->toContain('limit 10');
});

it('compiles lock for update', function () {
    $sql = TestLead::lockForUpdate()->toSql();

    expect($sql)->toContain('FOR UPDATE');
});

it('compiles aggregate count', function () {
    $builder = TestLead::query()->getQuery();
    $builder->aggregate = ['function' => 'count', 'columns' => ['Id']];
    $grammar = $builder->grammar;

    $sql = $grammar->compileSelect($builder);

    expect($sql)->toContain('count(Id)')
        ->and($sql)->toContain('aggregate');
});

it('compiles orWhere', function () {
    $sql = TestLead::where('Email', 'a@test.com')
        ->orWhere('Email', 'b@test.com')
        ->toSql();

    expect($sql)->toContain("Email = 'a@test.com'")
        ->and($sql)->toContain("or Email = 'b@test.com'");
});

it('handles SOQL date literals', function () {
    $sql = TestLead::where('CreatedDate', '>=', 'THIS_WEEK')->toSql();

    // Date literals compiled without spaces around operator by whereDate/whereLiteral
    expect($sql)->toContain('CreatedDate')
        ->and($sql)->toContain('THIS_WEEK');
});

it('handles SOQL date literals with N parameter', function () {
    $sql = TestLead::where('CreatedDate', '>=', 'LAST_N_DAYS:30')->toSql();

    expect($sql)->toContain('CreatedDate')
        ->and($sql)->toContain('LAST_N_DAYS:30');
});
