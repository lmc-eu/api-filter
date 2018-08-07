<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class FilterApplicator
{
    /** @var PrioritizedCollection|ApplicatorInterface[] */
    private $applicators;

    public function __construct()
    {
        $this->applicators = new PrioritizedCollection(ApplicatorInterface::class);
    }

    public function registerApplicator(ApplicatorInterface $applicator, int $priority): void
    {
        $this->applicators->add($applicator, $priority);
    }

    public function apply(FilterInterface $filter, Filterable $filterable): Filterable
    {
        $applicator = $this->findApplicatorFor($filterable);

        if ($filter instanceof FilterWithOperator) {
            return $applicator->applyFilterWithOperator($filter, $filterable);
        }

        throw new \InvalidArgumentException(sprintf('Unsupported filter given "%s".', get_class($filter)));
    }

    private function findApplicatorFor(Filterable $filterable): ApplicatorInterface
    {
        foreach ($this->applicators as $applicator) {
            if ($applicator->supports($filterable)) {
                return $applicator;
            }
        }

        $filterableValue = $filterable->getValue();
        throw new \InvalidArgumentException(
            sprintf(
                'Unsupported filterable of type "%s".',
                is_object($filterableValue) ? get_class($filterableValue) : gettype($filterableValue)
            )
        );
    }

    public function getPreparedValue(FilterInterface $filter, Filterable $filterable): array
    {
        return $this
            ->findApplicatorFor($filterable)
            ->getPreparedValue($filter);
    }

    public function applyAll(FiltersInterface $filters, Filterable $filterable): Filterable
    {
        return $filters->applyAllTo($filterable, $this);
    }

    public function getPreparedValues(FiltersInterface $filters, Filterable $filterable): array
    {
        return $filters->getPreparedValues($this->findApplicatorFor($filterable));
    }
}
