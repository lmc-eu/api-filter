<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Escape\EscapeInterface;

class EscapeService implements EscapeInterface
{
    /** @var Storage|EscapeInterface[] */
    private $escapes;

    public function __construct()
    {
        $this->escapes = new Storage(EscapeInterface::class);
    }

    public function addEscape(EscapeInterface $escape, int $priority): void
    {
        $this->escapes->addItem($escape, $priority);
    }

    public function supports(string $column, Value $value): bool
    {
        foreach ($this->escapes as $escape) {
            if ($escape->supports($column, $value)) {
                return true;
            }
        }

        return false;
    }

    public function escape(string $column, Value $value): Value
    {
        foreach ($this->escapes as $escape) {
            if ($escape->supports($column, $value)) {
                return $escape->escape($column, $value);
            }
        }

        throw new \LogicException(
            sprintf(
                'You must call supports method first so there will be at least one escape for value "%s".',
                var_export($value->getValue(), true)
            )
        );
    }
}
