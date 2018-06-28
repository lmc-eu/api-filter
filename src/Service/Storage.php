<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Assert\Assertion;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Tuple;

class Storage implements \IteratorAggregate
{
    /** @var string */
    private $type;
    /** @var ITuple[] (Type, priority) */
    private $items;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->items = [];
    }

    /** @param mixed $item Where mixed is $this->type */
    public function addItem($item, int $priority): void
    {
        Assertion::isInstanceOf(
            $item,
            $this->type,
            sprintf('Given item is not "%s" it is "%s" instead.', $this->type, gettype($item))
        );

        $this->items[] = Tuple::of($item, $priority);
    }

    public function getIterator(): iterable
    {
        yield from $this->getItemsByPriority();
    }

    /** @return mixed[] Where mixed is $this–>type */
    private function getItemsByPriority(): iterable
    {
        $items = $this->items;
        usort(
            $items,
            function (Tuple $a, Tuple $b): int {
                return $b->second() <=> $a->second();
            }
        );

        foreach ($items as [$item]) {
            yield $item;
        }
    }
}
