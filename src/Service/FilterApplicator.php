<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filters\FiltersInterface;

class FilterApplicator
{
    /** @var Storage|ApplicatorInterface[] */
    private $applicators;

    public function __construct()
    {
        $this->applicators = new Storage(ApplicatorInterface::class);
    }

    public function registerApplicator(ApplicatorInterface $applicator, int $priority): void
    {
        $this->applicators->addItem($applicator, $priority);
    }

    public function apply(FilterInterface $filter, Filterable $filterable): Filterable
    {
        return $this
            ->findApplicatorFor($filterable)
            ->applyTo($filter, $filterable);
    }

    private function findApplicatorFor(Filterable $filterable): ApplicatorInterface
    {
        foreach ($this->applicators as $applicator) {
            if ($applicator->supports($filterable)) {
                return $applicator;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported filterable "%s".', var_export($filterable->getValue(), true))
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
        return $filters->applyAllTo($this->findApplicatorFor($filterable), $filterable);
    }

    public function getPreparedValues(FiltersInterface $filters, Filterable $filterable): array
    {
        return $filters->getPreparedValues($this->findApplicatorFor($filterable));
    }
}
