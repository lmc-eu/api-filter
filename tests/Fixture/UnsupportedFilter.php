<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Fixture;

use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterInterface;

class UnsupportedFilter implements FilterInterface
{
    public function getColumn(): string
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }

    public function getValue(): Value
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }

    public function getTitle(): string
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }
}
