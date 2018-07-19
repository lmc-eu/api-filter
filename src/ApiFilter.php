<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Applicator\ApplicatorSql;
use Lmc\ApiFilter\Applicator\DoctrineQueryBuilderApplicator;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Escape\EscapeInt;
use Lmc\ApiFilter\Escape\EscapeInterface;
use Lmc\ApiFilter\Escape\EscapeString;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filters\FiltersInterface;
use Lmc\ApiFilter\Service\EscapeService;
use Lmc\ApiFilter\Service\FilterApplicator;
use Lmc\ApiFilter\Service\QueryParametersParser;

class ApiFilter
{
    /** @var QueryParametersParser */
    private $parser;
    /** @var FilterApplicator */
    private $applicator;
    /** @var EscapeService */
    private $escapeService;

    public function __construct()
    {
        $this->parser = new QueryParametersParser();
        $this->escapeService = new EscapeService();
        $this->applicator = new FilterApplicator($this->escapeService);

        if (class_exists('Doctrine\ORM\QueryBuilder')) {
            $this->registerApplicator(new DoctrineQueryBuilderApplicator(), Priority::MEDIUM);
        }
        $this->registerApplicator(new ApplicatorSql(), Priority::MEDIUM);

        $this->registerEscape(new EscapeInt(), Priority::MEDIUM);
        $this->registerEscape(new EscapeString(), Priority::LOW);
    }

    /**
     * Parse query parameters into FiltersInterface, which can be applied later on some `filterable`
     *
     * @example
     * With Symfony
     * $filters = $apiFilter->parseFilters($request->query->all())
     */
    public function parseFilters(array $queryParameters): FiltersInterface
    {
        return $this->parser->parse($queryParameters);
    }

    /**
     * Apply one Filter to given filterable and returns the result of the same type as given filterable
     * or whatever the Applicator returns
     *
     * Filterable might be anything, but there must be an Applicator for that filterable
     * First Applicator (from the highest priority), which can be applied is applied and no others
     * @see ApplicatorInterface
     *
     * You can register you own Applicators
     * @see ApiFilter::registerApplicator()
     *
     * @param mixed $filterable
     * @return mixed
     */
    public function applyFilter(FilterInterface $filter, $filterable)
    {
        return $this->applicator->apply($filter, new Filterable($filterable))->getValue();
    }

    /**
     * Apply all Filters to given filterable and returns the result of the same type as given filterable
     * or whatever the Applicator returns
     *
     * Filterable might be anything, but there must be an Applicator for that filterable
     * First Applicator (from the highest priority), which can be applied is applied and no others
     * @see ApplicatorInterface
     *
     * You can register you own Applicators
     * @see ApiFilter::registerApplicator()
     *
     * @param mixed $filterable
     * @return mixed
     */
    public function applyFilters(FiltersInterface $filters, $filterable)
    {
        return $this->applicator->applyAll($filters, new Filterable($filterable))->getValue();
    }

    /**
     * Add another applicator which will be try to use on apply filter(s)
     * First Applicator (from the highest priority), which can be applied is applied and no others
     *
     * Priority can be any integer value (or use predefined Priority)
     * @see Priority
     */
    public function registerApplicator(ApplicatorInterface $applicator, int $priority): void
    {
        $this->applicator->registerApplicator($applicator, $priority);
    }

    /**
     * Add another escape which will be try to use on values before while applying the filter
     * First Escape (from the highest priority), which can be applied is applied and no others
     *
     * Priority can be any integer value (or use predefined Priority)
     * @see Priority
     */
    public function registerEscape(EscapeInterface $escape, int $priority): void
    {
        $this->escapeService->addEscape($escape, $priority);
    }
}
