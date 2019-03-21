<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\ApiFilterExceptionInterface;
use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Filters\Filters;

/** @covers \Lmc\ApiFilter\Service\FunctionCreator */
class FunctionCreatorTest extends AbstractTestCase
{
    /** @var FunctionCreator */
    private $functionCreator;
    /** @var FilterApplicator */
    private $filterApplicator;
    /** @var Functions */
    private $functions;

    protected function setUp(): void
    {
        $this->functions = new Functions();
        $this->filterApplicator = new FilterApplicator($this->functions);

        $this->filterApplicator->registerApplicator(new SqlApplicator(), 1);

        $this->functionCreator = new FunctionCreator(
            new FilterFactory()
        );
    }

    /**
     * @test
     * @dataProvider provideParameters
     */
    public function shouldTransformParametersIntoFunctionParameterNames(array $parameters, array $expected): void
    {
        $result = $this->functionCreator->getParameterNames($this->functionCreator->normalizeParameters($parameters));

        $this->assertSame($expected, $result);
    }

    public function provideParameters(): array
    {
        return [
            // parameters, expected names
            'empty' => [[], []],
            'by array of names' => [['firstName', 'surname'], ['firstName', 'surname']],
            'by array of explicit definitions' => [[['firstName', 'eq'], ['surname', 'eq']], ['firstName', 'surname']],
            'by array of Parameters' => [
                [new ParameterDefinition('firstName', 'eq'), new ParameterDefinition('surname', 'eq')],
                ['firstName', 'surname'],
            ],
            'by mixed' => [
                [new ParameterDefinition('firstName', 'eq'), 'middleName', ['surname', 'eq']],
                ['firstName', 'middleName', 'surname'],
            ],
            'by mixed with defaults' => [
                [
                    ParameterDefinition::equalToDefaultValue('firstName', new Value('Jon')),
                    'middleName',
                    ['surname', null, null, 'Snow'],
                ],
                ['middleName'],
            ],
        ];
    }

    /**
     * @test
     */
    public function shouldCreateFunctionWithImplicitFiltersAndApplyIt(): void
    {
        $sql = 'SELECT * FROM person';

        $firstName = new FunctionParameter('firstName', new Value('Jon'));
        $surname = new FunctionParameter('surname', new Value('Snow'));
        $this->filterApplicator->setFilters(Filters::from([$firstName, $surname]));

        $parameters = ['firstName', 'surname'];
        $functionWithImplicitFilters = $this->functionCreator->createByParameters(
            $this->filterApplicator,
            $this->functionCreator->normalizeParameters($parameters)
        );
        $this->functions->register('fullName', $parameters, $functionWithImplicitFilters);

        $result = $functionWithImplicitFilters($sql, $firstName, $surname);
        $this->assertSame(
            'SELECT * FROM person WHERE 1 AND firstName = :firstName_function_parameter AND surname = :surname_function_parameter',
            $result
        );

        $this->assertSame('firstName_function_parameter', $firstName->getTitle());
        $this->assertSame('surname_function_parameter', $surname->getTitle());
    }

    /**
     * @test
     * @dataProvider providePerfectBookParameters
     */
    public function shouldCreateFunctionWithExplicitFiltersAndApplyIt(array $parameters): void
    {
        $sql = 'SELECT * FROM person';
        $expectedResult = 'SELECT * FROM person WHERE 1 ' .
            'AND age > :ageFrom_function_parameter AND age < :ageTo_function_parameter ' .
            'AND size IN (:size_function_parameter_0, :size_function_parameter_1) ' .
            'AND genre = :genre_function_parameter';

        $ageFrom = new FunctionParameter('ageFrom', new Value(18));
        $ageTo = new FunctionParameter('ageTo', new Value(30));
        $size = new FunctionParameter('size', new Value(['A4', 'A5']));
        $this->filterApplicator->setFilters(Filters::from([$ageFrom, $ageTo, $size]));

        $functionWithImplicitFilters = $this->functionCreator->createByParameters(
            $this->filterApplicator,
            $this->functionCreator->normalizeParameters($parameters)
        );
        $this->functions->register('fullName', ['ageFrom', 'ageTo', 'size'], $functionWithImplicitFilters);

        $result = $functionWithImplicitFilters($sql, $ageFrom, $ageTo, $size);
        $this->assertSame($expectedResult, $result);

        $this->assertSame('ageFrom_function_parameter', $ageFrom->getTitle());
        $this->assertSame('ageTo_function_parameter', $ageTo->getTitle());
        $this->assertSame('size_function_parameter', $size->getTitle());
    }

    public function providePerfectBookParameters(): array
    {
        return [
            // parameters
            'by array' => [
                [
                    ['ageFrom', 'gt', 'age'],
                    ['ageTo', 'lt', 'age'],
                    ['size', 'in'],
                    ['genre', null, null, 'fantasy'],
                ],
            ],
            'by parameters' => [
                [
                    new ParameterDefinition('ageFrom', 'gt', 'age'),
                    new ParameterDefinition('ageTo', 'lt', 'age'),
                    new ParameterDefinition('size', 'in'),
                    new ParameterDefinition('genre', null, null, new Value('fantasy')),
                ],
            ],
            'by array + parameters' => [
                [
                    ['ageFrom', 'gt', 'age'],
                    new ParameterDefinition('ageTo', 'lt', 'age'),
                    ['size', 'in'],
                    ParameterDefinition::equalToDefaultValue('genre', new Value('fantasy')),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidParameters
     */
    public function shouldNotCreateFunctionWithInvalidParameter(array $parameters, string $expectedMessage): void
    {
        $this->expectException(ApiFilterExceptionInterface::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->functionCreator->createByParameters(
            $this->filterApplicator,
            $this->functionCreator->normalizeParameters($parameters)
        );
    }

    public function provideInvalidParameters(): array
    {
        return [
            // parameters, expectedMessage
            'int' => [
                [1],
                'Parameter for function creator must be either string, array or instance of Lmc\ApiFilter\Entity\Parameter but "integer" given.',
            ],
            'applicator' => [
                [new Value('foo')],
                'Parameter for function creator must be either string, array or instance of Lmc\ApiFilter\Entity\Parameter but "Lmc\ApiFilter\Entity\Value" given.',
            ],
        ];
    }

    /**
     * @test
     */
    public function shouldNotApplyFunctionWithoutAllParameters(): void
    {
        $firstName = new FunctionParameter('firstName', new Value('Jon'));
        $this->filterApplicator->setFilters(Filters::from([$firstName]));

        $parameters = ['firstName', 'surname'];
        $functionWithImplicitFilters = $this->functionCreator->createByParameters(
            $this->filterApplicator,
            $this->functionCreator->normalizeParameters($parameters)
        );
        $this->functions->register('fullName', $parameters, $functionWithImplicitFilters);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "surname" is required and must have a value.');

        $functionWithImplicitFilters('SELECT * FROM person', $firstName);
    }

    /**
     * @test
     */
    public function shouldGetParameterDefinitions(): void
    {
        $parameters = [['ageFrom', 'gt', 'age'], 'firstName'];
        $expected = [new ParameterDefinition('ageFrom', 'gt', 'age'), new ParameterDefinition('firstName')];
        $parameters = $this->functionCreator->normalizeParameters($parameters);

        $result = $this->functionCreator->getParameterDefinitions($parameters);

        $this->assertEquals($expected, $result);
    }
}
