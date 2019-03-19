<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\Entity\Value;

interface FilterInterface
{
    public function getColumn(): string;

    public function getValue(): Value;

    public function getTitle(): string;

    public function setFullTitle(string $title): void;
}
