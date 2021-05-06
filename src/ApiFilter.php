<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Applicator\QueryBuilderApplicator;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Exception\ApiFilterExceptionInterface;
use Lmc\ApiFilter\Exception\TupleException;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filters\FiltersInterface;
use Lmc\ApiFilter\Service\FilterApplicator;
use Lmc\ApiFilter\Service\FilterFactory;
use Lmc\ApiFilter\Service\FunctionCreator;
use Lmc\ApiFilter\Service\Functions;
use Lmc\ApiFilter\Service\QueryParametersParser;
use MF\Collection\Exception\TupleExceptionInterface;
use MF\Collection\Immutable\ITuple;
use MF\Collection\Immutable\Tuple;

class ApiFilter
{
    private Functions $functions;
    private QueryParametersParser $parser;
    private FilterApplicator $applicator;
    private FunctionCreator $functionCreator;

    public function __construct()
    {
        $filterFactory = new FilterFactory();
        $this->functions = new Functions();
        $this->parser = new QueryParametersParser($filterFactory, $this->functions);
        $this->applicator = new FilterApplicator($this->functions);
        $this->functionCreator = new FunctionCreator($filterFactory);

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
     *
     * @throws ApiFilterExceptionInterface
     * @return FiltersInterface|FilterInterface[]
     */
    public function parseFilters(array $queryParameters): FiltersInterface
    {
        $filters = $this->parser->parse($queryParameters);
        $this->applicator->setFilters($filters);

        return $filters;
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
     * @param mixed $filterable of type <T> - this must be supported by any applicator
     * @throws ApiFilterExceptionInterface
     * @return mixed of type <T> - same as given filterable
     */
    public function applyFilter(FilterInterface $filter, $filterable, FiltersInterface $filters = null)
    {
        if ($filters) {
            $this->applicator->setFilters($filters);
        }

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
     * @param mixed $filterable of type <T>
     * @throws ApiFilterExceptionInterface
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
     * @param mixed $filterable of type <T> - this must be supported by any applicator
     * @throws ApiFilterExceptionInterface
     * @return mixed of type <T> - same as given filterable
     */
    public function applyFilters(FiltersInterface $filters, $filterable)
    {
        $this->applicator->setFilters($filters);

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
     * @param mixed $filterable of type <T>
     * @throws ApiFilterExceptionInterface
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
    public function registerApplicator(ApplicatorInterface $applicator, int $priority): self
    {
        $this->applicator->registerApplicator($applicator, $priority);

        return $this;
    }

    /**
     * Declare a function to specify a name for several parameters which must be given together.
     *
     * ApiFilter::applyFilters() method will be used with all declared parameters.
     * If you want to have a custom callback, not just abstract a name for few parameters, use registerFunction method instead
     *
     * Note:
     * It is not allowed to register more functions with same parameter (not matter of their order).
     *
     * @example
     * How to abstract first and last name into a fullName function and still benefit from ApiFilter features
     * $apiFilter->declareFunction('fullName', ['first', 'last']);
     *
     * @see ApiFilter::registerFunction()
     * @see ApiFilter::executeFunction()
     *
     * Parameters might be defined as
     * - array of single values (names)
     * - array of array values (definitions)
     * - array of ParameterDefinition
     *
     * @param array $parameters names or definition of needed parameters (parameters will be passed to function in given order)
     * @throws ApiFilterExceptionInterface
     */
    public function declareFunction(string $functionName, array $parameters): self
    {
        $parameters = $this->functionCreator->normalizeParameters($parameters);

        $this->functions->register(
            $functionName,
            $this->functionCreator->getParameterNames($parameters),
            $this->functionCreator->createByParameters($this->applicator, $parameters),
            $this->functionCreator->getParameterDefinitions($parameters)
        );

        return $this;
    }

    /**
     * Add a custom function to express any intention you can have
     *
     * Note:
     * You must not register more functions with same parameters (not matter of their order).
     *
     * @example
     * How to abstract first and last name into a fullName function and still benefit from ApiFilter features
     * $apiFilter->registerFunction(
     *      'fullName',
     *      ['first', 'last'],
     *      function($filterable, FunctionParameter $first, FunctionParameter $last) use ($apiFilter) {
     *          return $apiFilter->applyFilters(Filters::from([$first, $last], $filterable);
     *      }
     * );
     * In this case it is the same as declareFunction method (see example there)
     *
     * @example
     * How to completely bypass ApiFilter and directly search in elastic search (other storage)
     * $apiFilter->registerFunction(
     *      'elastic',
     *      ['query'],
     *      function($filterable, FunctionParameter $query) use ($elasticClient) {
     *          return $elasticClient->query($query->getValue()->getValue());
     *      }
     * );
     * In this case it is advised to execute the elastic function directly by executeFunction method (see example there)
     *
     * @see ApiFilter::declareFunction()
     * @see ApiFilter::executeFunction()
     *
     * @param array $parameters names of needed parameters (parameters will be passed to function in given order)
     * @param callable $function (Filterable<T> $filterable, FunctionParameter ...$parameters): Filterable<T>
     * @throws ApiFilterExceptionInterface
     */
    public function registerFunction(string $functionName, array $parameters, callable $function): self
    {
        $this->functions->register($functionName, $parameters, $function);

        return $this;
    }

    /**
     * Execute a function with parsed query parameters but without any implicit application
     * This allows you to bypass any applicator or not to implement one if you need to
     *
     * It will just parse filters and call a registered function with parsed filters
     *
     * @example
     * Executing a function, which bypasses ApiFilter and directly calls elastic search (see example of registerFunction)
     * $resultFromElastic = $apiFilter->executeFunction('elastic', $request->query->all(), null);
     *
     * @see ApiFilter::declareFunction()
     * @see ApiFilter::registerFunction()
     * @see ApiFilter::applyFunction() if you want apply function with applicators and get prepared values as well
     *
     * @param mixed $filterable of type <T> - this might not be supported by any applicator (if you don't use apply methods of ApiFilter)
     * @throws ApiFilterExceptionInterface
     * @return mixed of type <U> - the output of the registered function
     */
    public function executeFunction(string $functionName, array $queryParameters, mixed $filterable): mixed
    {
        $filters = $this->parser->parse($queryParameters);
        $this->applicator->setFilters($filters);

        return $this->functions
            ->execute($functionName, $filters, new Filterable($filterable))
            ->getValue();
    }

    /**
     * Apply a function with parsed query parameters and prepare values
     *
     * It will parse filters and apply registered function with parsed filters + prepare values for applied function
     *
     * @example
     * Applying a function directly on filterable
     * [$sql, $preparedValues] = $apiFilter
     *      ->declareFunction('fullName', ['firstName', 'surname'])
     *      ->applyFunction('fullName', ['fullName' => '(Jon, Snow)'], 'SELECT * FROM person');
     *
     * $sql:            SELECT * FROM person WHERE firstName = :firstName_function_parameter AND surname = :surname_function_parameter
     * $preparedValues: ['firstName_function_parameter' => 'Jon', 'surname_function_parameter' => 'Snow']
     *
     * @see ApiFilter::declareFunction()
     * @see ApiFilter::registerFunction()
     * @see ApiFilter::executeFunction() if you just want to bypass ApiFilter applicators
     *
     * @param mixed $filterable of type <T>
     * @throws ApiFilterExceptionInterface
     * @return ITuple (<U>, array) where <U> is the output of the registered function and array contains prepared values
     */
    public function applyFunction(string $functionName, array $queryParameters, mixed $filterable): ITuple
    {
        try {
            $filters = $this->parser->parse($queryParameters);
            $this->applicator->setFilters($filters);
            $filterable = new Filterable($filterable);

            $appliedFilterable = $this->functions->execute($functionName, $filters, $filterable);
            $preparedValues = $this->applicator->getPreparedValues($filters, $filterable);

            return Tuple::of($appliedFilterable->getValue(), $preparedValues);
        } catch (TupleExceptionInterface $e) {
            throw TupleException::forBaseTupleException($e);
        }
    }
}
