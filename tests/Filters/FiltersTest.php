<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filters;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FilterIn;
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

        $result = $this->filtersToArray($filters);

        $this->assertSame($items, $result);
    }

    private function filtersToArray($filters): array
    {
        $result = [];
        foreach ($filters as $filter) {
            $result[] = $filter;
        }

        return $result;
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

        $result = $this->filtersToArray($filters);

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

        $result = $this->filtersToArray($filters);

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

        $result = $this->filtersToArray($filters);

        $this->assertEquals($expected, $result);
    }
}
