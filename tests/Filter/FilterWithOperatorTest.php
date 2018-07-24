<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;

class FilterWithOperatorTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function shouldCreateFilterWithTitle(): void
    {
        $filter = new FilterWithOperator('column', new Value('value'), '=', 'eq');

        $title = $filter->getTitle();

        $this->assertSame('eq', $title);
    }

    /**
     * @test
     * @dataProvider provideInvalidTitle
     */
    public function shouldNotCreateFilterWithInvalidTitle(string $invalidTitle, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new FilterWithOperator('column', new Value('value'), '=', $invalidTitle);
    }

    public function provideInvalidTitle(): array
    {
        return [
            // invalid title, expected message
            'empty' => ['', 'Title must be only [a-z] letters but "" given.'],
            'upper case' => ['Title', 'Title must be only [a-z] letters but "Title" given.'],
            'dash' => ['tit-le', 'Title must be only [a-z] letters but "tit-le" given.'],
            'underscore' => ['tit_le', 'Title must be only [a-z] letters but "tit_le" given.'],
        ];
    }
}
