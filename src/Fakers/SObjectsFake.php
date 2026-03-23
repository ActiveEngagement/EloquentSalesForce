<?php

namespace Lester\EloquentSalesForce\Fakers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lester\EloquentSalesForce\Database\SOQLBatch;
use Lester\EloquentSalesForce\SObjects;
use PHPUnit\Framework\Assert as PHPUnit;

class SObjectsFake
{
    protected SObjects $instance;

    protected bool $authenticated = false;

    protected array $commands = [];

    protected array $queryResponses = [];

    protected array $describeResponses = [];

    protected array $picklistResponses = [];

    protected Collection $queryHistory;

    protected int $idCounter = 1;

    public function __construct(SObjects $instance)
    {
        $this->instance = $instance;
        $this->queryHistory = collect();
    }

    public function authenticate(): bool
    {
        $this->authenticated = true;

        return true;
    }

    public function authorize(): void
    {
        $this->authenticated = true;
    }

    public function instanceUrl(): string
    {
        return 'https://test.salesforce.com';
    }

    public function query(string $soql): array
    {
        $this->commands['query'][] = $soql;
        $this->queryHistory->push($soql);

        foreach ($this->queryResponses as $pattern => $response) {
            if ($pattern === '*' || Str::contains($soql, $pattern)) {
                return is_callable($response) ? $response($soql) : $response;
            }
        }

        return ['totalSize' => 0, 'done' => true, 'records' => []];
    }

    public function queryAll(string $soql): array
    {
        $this->commands['queryAll'][] = $soql;
        $this->queryHistory->push($soql);

        foreach ($this->queryResponses as $pattern => $response) {
            if ($pattern === '*' || Str::contains($soql, $pattern)) {
                return is_callable($response) ? $response($soql) : $response;
            }
        }

        return ['totalSize' => 0, 'done' => true, 'records' => []];
    }

    public function sobjects(string $path, array $options = []): mixed
    {
        $method = $options['method'] ?? 'get';
        $this->commands['sobjects'][] = compact('path', 'options');

        if ($method === 'post') {
            $id = $this->generateId();

            return ['success' => true, 'id' => $id];
        }

        if ($method === 'patch') {
            return null;
        }

        if ($method === 'delete') {
            return null;
        }

        // GET - describe or layout
        if (isset($this->describeResponses[$path])) {
            return $this->describeResponses[$path];
        }

        return [];
    }

    public function composite(string $path, array $options = []): array
    {
        $method = $options['method'] ?? 'get';
        $this->commands['composite'][] = compact('path', 'options');

        if (Str::startsWith($path, 'tree/')) {
            $records = $options['body']['records'] ?? [];
            $results = collect($records)->map(fn ($r, $i) => [
                'referenceId' => 'ref' . $i,
                'id' => $this->generateId(),
            ])->all();

            return ['results' => $results];
        }

        if ($path === 'sobjects') {
            if ($method === 'delete') {
                return [];
            }
            if ($method === 'patch') {
                return [];
            }
        }

        if ($path === 'batch') {
            $requests = $options['body']['batchRequests'] ?? [];
            $results = [];
            foreach ($requests as $request) {
                $url = $request['url'] ?? '';
                $soql = urldecode(Str::after($url, 'q='));
                $queryResult = $this->query($soql);
                $results[] = [
                    'statusCode' => 200,
                    'result' => $queryResult,
                ];
            }

            return ['results' => $results];
        }

        return [];
    }

    public function describe(string $object, ?string $key = null, $filter = null): mixed
    {
        $this->commands['describe'][] = $object;

        if (isset($this->describeResponses[$object])) {
            $data = $this->describeResponses[$object];

            return $key !== null ? data_get($data, $key) : $data;
        }

        return $key !== null ? null : [];
    }

    public function versions(): array
    {
        return [['version' => '59.0', 'label' => 'Spring \'24', 'url' => '/services/data/v59.0']];
    }

    public function getPicklistValues(string $object, string $field): Collection
    {
        $key = "$object.$field";

        return collect($this->picklistResponses[$key] ?? []);
    }

    public function next(string $url): array
    {
        return ['totalSize' => 0, 'done' => true, 'records' => []];
    }

    public function update(Collection $collection, bool $allOrNone = false): void
    {
        $this->authenticate();
        $this->commands['update'][] = $collection;
    }

    public function log(string $message = '', mixed $details = [], string $level = 'info'): void
    {
        // no-op
    }

    public function isSalesForceId(string $str): bool
    {
        return $this->instance->isSalesForceId($str);
    }

    public function convert(string $str): string
    {
        return $this->instance->convert($str);
    }

    public function object(array $attributes = []): \Lester\EloquentSalesForce\SalesForceObject
    {
        return $this->instance->object($attributes);
    }

    public function queryHistory(): Collection
    {
        return $this->queryHistory;
    }

    public function processExceptions(array $exceptions): void
    {
        $this->instance->processExceptions($exceptions);
    }

    public function saleforceAttempt(\Closure $callback): mixed
    {
        return $this->instance->saleforceAttempt($callback);
    }

    public function getBatch(): SOQLBatch
    {
        return new SOQLBatch([]);
    }

    public function runBatch(array &$errors = []): SOQLBatch
    {
        return $this->getBatch();
    }

    // --- Configuration methods for tests ---

    public function withQueryResponse(string $pattern, array|callable $response): static
    {
        $this->queryResponses[$pattern] = $response;

        return $this;
    }

    public function withDescribeResponse(string $key, array $response): static
    {
        $this->describeResponses[$key] = $response;

        return $this;
    }

    public function withPicklistValues(string $object, string $field, array $values): static
    {
        $this->picklistResponses["$object.$field"] = $values;

        return $this;
    }

    // --- Assertion methods ---

    public function assertAuthenticated(): void
    {
        PHPUnit::assertTrue($this->authenticated, 'SObjects was not authenticated.');
    }

    public function assertQueried(string $pattern = null): void
    {
        $queries = $this->commands['query'] ?? [];
        if ($pattern === null) {
            PHPUnit::assertNotEmpty($queries, 'No queries were executed.');

            return;
        }

        $found = collect($queries)->contains(fn ($q) => Str::contains($q, $pattern));
        PHPUnit::assertTrue($found, "No query matching pattern [{$pattern}] was found.");
    }

    public function assertMassUpdate(Collection $collection): void
    {
        $this->assertAuthenticated();

        PHPUnit::assertTrue($this->updated($collection));
    }

    public function assertSobjectsCalled(string $path = null, string $method = null): void
    {
        $calls = $this->commands['sobjects'] ?? [];
        if ($path === null) {
            PHPUnit::assertNotEmpty($calls, 'No sobjects calls were made.');

            return;
        }

        $found = collect($calls)->contains(function ($call) use ($path, $method) {
            $pathMatch = Str::contains($call['path'], $path);
            $methodMatch = $method === null || ($call['options']['method'] ?? 'get') === $method;

            return $pathMatch && $methodMatch;
        });

        PHPUnit::assertTrue($found, "No sobjects call matching path [{$path}]" . ($method ? " with method [{$method}]" : '') . ' was found.');
    }

    public function assertCompositeCalled(string $path = null, string $method = null): void
    {
        $calls = $this->commands['composite'] ?? [];
        if ($path === null) {
            PHPUnit::assertNotEmpty($calls, 'No composite calls were made.');

            return;
        }

        $found = collect($calls)->contains(function ($call) use ($path, $method) {
            $pathMatch = $call['path'] === $path || Str::contains($call['path'], $path);
            $methodMatch = $method === null || ($call['options']['method'] ?? 'get') === $method;

            return $pathMatch && $methodMatch;
        });

        PHPUnit::assertTrue($found, "No composite call matching path [{$path}]" . ($method ? " with method [{$method}]" : '') . ' was found.');
    }

    protected function updated(Collection $collection): bool
    {
        if (! isset($this->commands['update'])) {
            return false;
        }
        foreach ($this->commands['update'] as $command) {
            return $command == $collection;
        }

        return false;
    }

    protected function generateId(): string
    {
        $id = str_pad((string) $this->idCounter++, 15, '0', STR_PAD_LEFT);

        return '001' . $id . 'AAA';
    }
}
