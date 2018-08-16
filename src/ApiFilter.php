<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Applicator\QueryBuilderApplicator;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filters\FiltersInterface;
use Lmc\ApiFilter\Service\FilterApplicator;
use Lmc\ApiFilter\Service\QueryParametersParser;

class ApiFilter
{
    /** @var QueryParametersParser */
    private $parser;
    /** @var FilterApplicator */
    private $applicator;

    public function __construct()
    {
        $this->parser = new QueryParametersParser();
        $this->applicator = new FilterApplicator();

        if (class_exists('Doctrine\ORM\QueryBuilder')) {
            $this->registerApplicator(new QueryBuilderApplicator(), Priority::MEDIUM);
        }
    }

    /**
     * Parse query parameters into FiltersInterface, which can be applied later on some `filterable`
     *
     * @example
     * With Symfony
     * $queryParameters = $request->query->all();   // ['field' => 'value']
     * $filters = $apiFilter->parseFilters($queryParameters)
     *
     * // [
     * //     0 => Lmc\ApiFilter\Filter\FilterWithOperator {
     * //         private $title    => 'eq'
     * //         private $operator => '='
     * //         private $column   => 'field'
     * //         private $value    => Lmc\ApiFilter\Entity\Value {
     * //             private $value = 'value'
     * //         }
     * //     }
     * // ]
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
     * You can register your own Applicators
     * @see ApiFilter::registerApplicator()
     *
     * @example
     * $sql = 'SELECT * FROM table';
     * [$firstFilter] = $apiFilter->parseFilters(['title' => 'foo']);      // FilterWithOperator('title', new Value('foo'), '=', 'eq')
     * $sqlWithFilter = $apiFilter->applyFilter($firstFilters, $sql);      // SELECT * FROM table WHERE title = :title_eq
     * $preparedValue = $apiFilter->getPreparedValue($firstFilters, $sql); // ['title_eq' => 'foo']
     *
     * @param mixed $filterable
     * @return mixed
     */
    public function applyFilter(FilterInterface $filter, $filterable)
    {
        return $this->applicator->apply($filter, new Filterable($filterable))->getValue();
    }

    /**
     * Prepared value for applied filter
     *
     * Note: Both Filter and Filterable must be the same as was for apply method
     * @see ApiFilter::applyFilter()
     *
     * @example
     * $sql = 'SELECT * FROM table';
     * [$firstFilter] = $apiFilter->parseFilters(['title' => 'foo']);      // FilterWithOperator('title', new Value('foo'), '=', 'eq')
     * $sqlWithFilter = $apiFilter->applyFilter($firstFilters, $sql);      // SELECT * FROM table WHERE title = :title_eq
     * $preparedValue = $apiFilter->getPreparedValue($firstFilters, $sql); // ['title_eq' => 'foo']
     *
     * @param mixed $filterable
     */
    public function getPreparedValue(FilterInterface $filter, $filterable): array
    {
        return $this->applicator->getPreparedValue($filter, new Filterable($filterable));
    }

    /**
     * Apply all Filters to given filterable and returns the result of the same type as given filterable
     * or whatever the Applicator returns
     *
     * Filterable might be anything, but there must be an Applicator for that filterable
     * First Applicator (from the highest priority), which can be applied is applied and no others
     * @see ApplicatorInterface
     *
     * You can register your own Applicators
     * @see ApiFilter::registerApplicator()
     *
     * @example
     * $sql = 'SELECT * FROM table';
     * $filters = $apiFilter->parseFilters(['title' => 'foo']);         // [Filter('title', 'foo', '=')]
     * $sqlWithFilters = $apiFilter->applyFilters($filters, $sql);      // SELECT * FROM table WHERE title = :title_eq
     * $preparedValues = $apiFilter->getPreparedValues($filters, $sql); // ['title_eq' => 'foo']
     *
     * @param mixed $filterable
     * @return mixed
     */
    public function applyFilters(FiltersInterface $filters, $filterable)
    {
        return $this->applicator->applyAll($filters, new Filterable($filterable))->getValue();
    }

    /**
     * Prepared values for applied filters
     *
     * Note: Both Filters and Filterable must be the same as was for apply method
     * @see ApiFilter::applyFilters()
     *
     * @example
     * $sql = 'SELECT * FROM table';
     * $filters = $apiFilter->parseFilters(['title' => 'foo']);         // [Filter('title', 'foo', '=')]
     * $sqlWithFilters = $apiFilter->applyFilters($filters, $sql);      // SELECT * FROM table WHERE title = :title_eq
     * $preparedValues = $apiFilter->getPreparedValues($filters, $sql); // ['title_eq' => 'foo']
     *
     * @param mixed $filterable
     */
    public function getPreparedValues(FiltersInterface $filters, $filterable): array
    {
        return $this->applicator->getPreparedValues($filters, new Filterable($filterable));
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
}
