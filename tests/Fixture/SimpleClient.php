<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Fixture;

class SimpleClient
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function query(string $query): array
    {
        $data = $this->data;
        $data['query'] = $query;

        return $data;
    }
}
