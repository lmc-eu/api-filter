<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Applicator;

use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Escape\EscapeInterface;

abstract class AbstractApplicator implements ApplicatorInterface
{
    /** @var EscapeInterface|null */
    private $escape;

    public function setEscape(EscapeInterface $escape): void
    {
        $this->escape = $escape;
    }

    protected function escape(string $column, Value $value): Value
    {
        return $this->escape && $this->escape->supports($column, $value)
            ? $this->escape->escape($column, $value)
            : $value;
    }
}
