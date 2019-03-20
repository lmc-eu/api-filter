<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Exception\ApiFilterExceptionInterface;

class ApiFilterRegisterFunctionTest extends AbstractTestCase
{
    /** @var ApiFilter */
    private $apiFilter;
    /** @var QueryBuilder */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->setUpQueryBuilder();

        $this->apiFilter = new ApiFilter();
        $this->apiFilter->registerApplicator(new SqlApplicator(), Priority::HIGHEST);
    }

    /**
     * @test
     */
    public function shouldNotAllowToRegisterFunctionsWithSameParameters(): void
    {
        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage('There is already a function "person1" with parameter "name" registered. Parameters must be unique.');

        $this->apiFilter
            ->registerFunction(
                'person1',
                ['name', 'age'],
                $this->createDummyCallback('person1')
            )
            ->registerFunction(
                'person2',
                ['name', 'ageTo', 'ageFrom'],
                $this->createDummyCallback('person2')
            );
    }
}
