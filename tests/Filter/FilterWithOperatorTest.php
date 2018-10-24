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

        $this->assertSame('column_eq', $title);
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
            'empty' => ['', 'Title must be only [a-zA-Z_] letters but "" given.'],
            'dash' => ['tit-le', 'Title must be only [a-zA-Z_] letters but "tit-le" given.'],
            'space' => ['tit le', 'Title must be only [a-zA-Z_] letters but "tit le" given.'],
            'special char' => ['tit*le', 'Title must be only [a-zA-Z_] letters but "tit*le" given.'],
        ];
    }

    /**
     * @test
     */
    public function shouldOverrideDefaultTitleByFullTitle(): void
    {
        $filter = new FilterWithOperator('column', new Value('value'), '>', 'gt');
        $this->assertSame('column_gt', $filter->getTitle());

        $filter->setFullTitle('full_title');
        $this->assertSame('full_title', $filter->getTitle());
    }
}
