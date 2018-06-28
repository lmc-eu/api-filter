<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

class ApiFilterTest extends AbstractTestCase
{
    /** @var ApiFilter */
    private $apiFilter;

    protected function setUp(): void
    {
        $this->apiFilter = new ApiFilter();
    }

    /**
     * @test
     * @dataProvider provideQueryParametersForSql
     */
    public function shouldParseQueryParametersAndApplyThemToSimpleSql(
        array $queryParameters,
        string $sql,
        string $expectedSql
    ): void {
        $this->markTestIncomplete('todo');
    }

    public function provideQueryParametersForSql(): array
    {
        return [
            // empty
            'empty' => [
                [],
                'SELECT * FROM table',
                'SELECT * FROM table',
            ],
            'title=foo' => [
                ['title' => 'foo'],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE title = \'foo\'',
            ],
            'title[eq]=foobar' => [
                ['title' => ['eq' => 'foobar']],
                'SELECT * FROM table',
                'SELECT * FROM table WHERE title = \'foo\'',
            ],
        ];
    }
}
