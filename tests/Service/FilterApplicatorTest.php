<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filters\Filters;

class FilterApplicatorTest extends AbstractTestCase
{
    /** @var FilterApplicator */
    private $filterApplicator;

    protected function setUp(): void
    {
        $this->filterApplicator = new FilterApplicator(
            new EscapeService()
        );
    }

    /**
     * @test
     * @dataProvider provideFilter
     */
    public function shouldApplyFilter(FilterInterface $filter, string $filterable, string $expected): void
    {
        $this->filterApplicator->registerApplicator(new SqlApplicator(), 1);

        $result = $this->filterApplicator->apply($filter, new Filterable($filterable))->getValue();

        $this->assertSame($expected, $result);
    }

    public function provideFilter(): array
    {
        return [
            // filter, filterable, expected
            'eq' => [
                new FilterWithOperator('col', new Value('val'), '='),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col = val',
            ],
            'gt' => [
                new FilterWithOperator('col', new Value('val'), '>'),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col > val',
            ],
            'gte' => [
                new FilterWithOperator('col', new Value(10), '>='),
                'SELECT * FROM table WHERE public = 1',
                'SELECT * FROM table WHERE public = 1 AND col >= 10',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideFilters
     */
    public function shouldApplyAllFilters(array $filters, string $filterable, string $expected): void
    {
        $this->filterApplicator->registerApplicator(new SqlApplicator(), 1);

        $result = $this->filterApplicator->applyAll(Filters::from($filters), new Filterable($filterable))->getValue();

        $this->assertSame($expected, $result);
    }

    public function provideFilters(): array
    {
        return [
            // filters, filterable, expected
            'between' => [
                [
                    new FilterWithOperator('column', new Value('max'), '>'),
                    new FilterWithOperator('column', new Value('min'), '<'),
                ],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE 1 AND column > max AND column < min',
            ],
        ];
    }

    /**
     * @test
     */
    public function shouldThrowInvalidArgumentExceptionOnApplyFilterOnNotSupportedFilterable(): void
    {
        $filterable = new Filterable('string filterable');
        $filter = new FilterWithOperator('any', new Value('filter'), 'any');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported filterable "\'string filterable\'".');

        $this->filterApplicator->apply($filter, $filterable);
    }
}
