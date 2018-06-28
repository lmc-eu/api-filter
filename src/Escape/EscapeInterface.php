<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Escape;

use Lmc\ApiFilter\Entity\Value;

interface EscapeInterface
{
    public function supports(string $column, Value $value): bool;

    public function escape(string $column, Value $value): Value;
}
