<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filters;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;

class FiltersTest extends AbstractTestCase
{
    /**
     * @test
     * @dataProvider provideFilters
     */
    public function shouldCreateFilters(array $items): void
    {
        $filters = new Filters($items);

        $result = $filters->toArray();

        $this->assertSame($items, $result);
    }

    public function provideFilters(): array
    {
        return [
            // filters
            'empty' => [[]],
            'eq' => [[new FilterWithOperator('col', new Value('val'), '=', 'eq')]],
            'in' => [[new FilterIn('col', new Value([1, 2]))]],
            'between' => [
                [
                    new FilterWithOperator('col', new Value('min'), '>=', 'gte'),
                    new FilterWithOperator('col', new Value('max'), '<=', 'lte'),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideFilters
     */
    public function shouldCreateFiltersFrom(array $items): void
    {
        $filters = Filters::from($items);

        $result = $filters->toArray();

        $this->assertSame($items, $result);
    }

    /**
     * @test
     */
    public function shouldAddFilterToFilters(): void
    {
        $expected = [
            new FilterWithOperator('col', new Value('min'), '>=', 'gte'),
            new FilterWithOperator('col', new Value('max'), '<=', 'lte'),
            new FilterWithOperator('col', new Value('val'), '=', 'eq'),
        ];

        $filters = Filters::from([
            new FilterWithOperator('col', new Value('min'), '>=', 'gte'),
            new FilterWithOperator('col', new Value('max'), '<=', 'lte'),
        ]);

        $filters->addFilter(new FilterWithOperator('col', new Value('val'), '=', 'eq'));

        $result = $filters->toArray();

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function shouldMergeInFilterToFilters(): void
    {
        $expected = [
            new FilterWithOperator('col', new Value('val'), '=', 'eq'),
            new FilterIn('col', new Value([1, 2, 3])),
        ];

        $filters = Filters::from([]);

        $filters->addFilter(new FilterWithOperator('col', new Value('val'), '=', 'eq'));
        $filters->addFilter(new FilterIn('col', new Value(1)));
        $filters->addFilter(new FilterIn('col', new Value(2)));
        $filters->addFilter(new FilterIn('col', new Value([3])));

        $result = $filters->toArray();

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function shouldCountFilters(): void
    {
        $filters = new Filters();
        $this->assertCount(0, $filters);

        $filters->addFilter(new FilterWithOperator('column', new Value('value'), '<', 'lt'));
        $this->assertCount(1, $filters);
    }

    /**
     * @test
     * @dataProvider provideHasFilter
     */
    public function shouldHasFilter(array $filters, FilterInterface $filter, bool $expected): void
    {
        $all = new Filters($filters);

        $result = $all->hasFilter($filter);

        $this->assertSame($expected, $result);
    }

    public function provideHasFilter(): array
    {
        $filter1 = new FilterWithOperator('col1', new Value('val'), '=', 'eq');
        $filter2 = new FilterWithOperator('col2', new Value('val'), '=', 'eq');
        $filter3 = new FilterWithOperator('col3', new Value('val'), '=', 'eq');

        return [
            // filters, filter, expected
            'empty filters' => [[], $filter1, false],
            'has filter from one' => [[$filter1], $filter1, true],
            'has filter from more' => [[$filter1, $filter2], $filter2, true],
            'has not filter from one' => [[$filter1], $filter2, false],
            'has not filter from more' => [[$filter1, $filter2], $filter3, false],
        ];
    }
}
