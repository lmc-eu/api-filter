<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Constant\Filter;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;

class FilterFactoryTest extends AbstractTestCase
{
    private const COLUMN = 'column';

    /** @var FilterFactory */
    private $filterFactory;

    protected function setUp(): void
    {
        $this->filterFactory = new FilterFactory();
    }

    /**
     * @param mixed $rawValue of type <T>
     * @param mixed $expectedValue of type <T>
     *
     * @test
     * @dataProvider provideFilters
     */
    public function shouldCreateFilter(
        string $filter,
        string $expectedFilterClass,
        string $expectedTitle = null,
        $rawValue = 'value',
        $expectedValue = null
    ): void {
        $expectedTitle = $expectedTitle ?? sprintf('%s_%s', self::COLUMN, $filter);
        $expectedValue = $expectedValue ?? $rawValue;

        $result = $this->filterFactory->createFilter(self::COLUMN, $filter, new Value($rawValue));

        $this->assertInstanceOf(FilterInterface::class, $result);
        $this->assertSame(self::COLUMN, $result->getColumn());
        $this->assertSame($expectedValue, $result->getValue()->getValue());
        $this->assertSame($expectedTitle, $result->getTitle());
        $this->assertInstanceOf($expectedFilterClass, $result);
    }

    public function provideFilters(): array
    {
        return [
            // filter, expectedFilterClass, ?expectedTitle, ?rawValue, ?expectedValue
            'eq' => ['eq', FilterWithOperator::class],
            'gt' => ['gt', FilterWithOperator::class],
            'gte' => ['gte', FilterWithOperator::class],
            'lt' => ['lt', FilterWithOperator::class],
            'lte' => ['lte', FilterWithOperator::class],
            'in' => ['in', FilterIn::class, 'column_in', 'value', ['value']],
            'gte - mixed case' => ['GtE', FilterWithOperator::class, self::COLUMN . '_gte'],
            'function' => [
                'function',
                FilterFunction::class,
                'column_function',
                $this->createDummyCallback('function'),
            ],
            'function parameter' => ['function_parameter', FunctionParameter::class],
        ];
    }

    /**
     * @test
     */
    public function shouldNotCreateUnknownFilter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter "unknown" is not implemented. For column "column" with value "foo".');

        $this->filterFactory->createFilter(self::COLUMN, 'unknown', new Value('foo'));
    }

    /**
     * @test
     */
    public function shouldNotCreateUnknownFilterWithCallableValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter "unknown" is not implemented. For column "column" with value "callable".');

        $this->filterFactory->createFilter(self::COLUMN, 'unknown', new Value(function () {
            return 'this is callable';
        }));
    }

    /**
     * @test
     */
    public function shouldNotCreateFilterFunctionWithoutCallableValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for filter function must be callable. "not a callback" given.');

        $this->filterFactory->createFilter(self::COLUMN, Filter::FUNCTION, new Value('not a callback'));
    }
}
