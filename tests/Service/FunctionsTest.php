<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\InvalidArgumentException;
use Lmc\ApiFilter\Filter\FunctionParameter;

/**
 * @group unit
 */
class FunctionsTest extends AbstractTestCase
{
    /** @var Functions */
    private $functions;

    protected function setUp(): void
    {
        $this->functions = new Functions();
    }

    /**
     * @test
     */
    public function shouldRegisterFullNameFunction(): void
    {
        $sql = "SELECT * FROM person WHERE firstName = ':firstName' AND surname = ':surname'";
        $firstName = 'Jon';
        $surname = 'Snow';

        $this->functions->register(
            'fullName',
            ['firstName', 'surname'],
            function ($filterable, FunctionParameter $firstName, FunctionParameter $surname) {
                $this->assertSame('firstName', $firstName->getColumn());
                $firstNameValue = $firstName->getValue()->getValue();
                $this->assertSame('Jon', $firstNameValue);

                $this->assertSame('surname', $surname->getColumn());
                $surnameValue = $surname->getValue()->getValue();
                $this->assertSame('Snow', $surnameValue);

                return str_replace(
                    [':firstName', ':surname'],
                    [$firstNameValue, $surnameValue],
                    $filterable
                );
            }
        );

        // assert definition
        $this->assertTrue($this->functions->isFunctionRegistered('fullName'));

        // assert function definition
        $fullNameFunction = $this->functions->getFunction('fullName');
        $this->assertIsCallable($fullNameFunction);

        // assert parameters definition
        $parameters = $this->functions->getParametersFor('fullName');
        $this->assertSame(['firstName', 'surname'], $parameters);

        // assert execution
        $appliedSql = $fullNameFunction(
            $sql,
            new FunctionParameter('firstName', new Value($firstName)),
            new FunctionParameter('surname', new Value($surname))
        );
        $this->assertSame("SELECT * FROM person WHERE firstName = 'Jon' AND surname = 'Snow'", $appliedSql);
    }

    /**
     * @test
     */
    public function shouldNotReturnNotRegisteredFunctions(): void
    {
        $functionName = 'not-registered-function';
        $this->assertFalse($this->functions->isFunctionRegistered($functionName));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function "not-registered-function" is not registered.');

        $this->functions->getFunction($functionName);
    }

    /**
     * @test
     */
    public function shouldNotGetFunctionParametersOfNotRegisteredFunction(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function "not-registered-function" is not registered.');

        $this->functions->getParametersFor('not-registered-function');
    }

    /**
     * @test
     */
    public function shouldNotRegisterFunctionWithoutName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function name must be defined.');

        $this->functions->register('', ['foo', 'bar'], $this->createDummyCallback('empty-name'));
    }

    /**
     * @test
     */
    public function shouldNotRegisterFunctionWithoutParameters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Function "invalid-function" must have some parameters.');

        $this->functions->register('invalid-function', [], $this->createDummyCallback('invalid-function'));
    }

    /**
     * @test
     */
    public function shouldNotRegisterFunctionWithSameParametersAsOtherFunctionHas(): void
    {
        $this->functions->register('fullName', ['firstName', 'surname'], $this->createDummyCallback('fullName'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There is already a function "fullName" with parameter "firstName" registered. Parameters must be unique.');

        $this->functions->register('name', ['firstName', 'middleName', 'surname'], $this->createDummyCallback('name'));
    }

    /**
     * @test
     * @dataProvider provideFunctionsByParameter
     */
    public function shouldGetFunctionNamesByParameter(string $parameter, array $expectedFunctions): void
    {
        $this->functions->register('fullName', ['firstName', 'surname'], $this->createDummyCallback('fullName'));
        $this->functions->register('adult', ['ageFrom'], $this->createDummyCallback('adult'));

        $result = $this->iteratorToArray($this->functions->getFunctionNamesByParameter($parameter));

        $this->assertSame($expectedFunctions, $result);
    }

    public function provideFunctionsByParameter(): array
    {
        return [
            // parameter, expectedFunctions
            'unknown' => ['unknown', []],
            'fullName by firstName' => ['firstName', ['fullName']],
            'fullName by surname' => ['surname', ['fullName']],
            'adult by ageFrom' => ['ageFrom', ['adult']],
        ];
    }

    /**
     * @test
     * @dataProvider provideFunctionsByAllParameters
     */
    public function shouldGetFunctionNamesByAllParameters(array $parameters, array $expectedFunctions): void
    {
        $this->functions->register('fullName', ['firstName', 'surname'], $this->createDummyCallback('fullName'));
        $this->functions->register('adult', ['ageFrom'], $this->createDummyCallback('adult'));

        $result = $this->iteratorToArray($this->functions->getFunctionNamesByAllParameters($parameters));

        $this->assertSame($expectedFunctions, $result);
    }

    public function provideFunctionsByAllParameters(): array
    {
        return [
            // parameters, expectedFunctions
            'unknown' => [['unknown'], []],
            'fullName' => [['firstName', 'surname'], ['fullName']],
            'fullName - reverse order' => [['surname', 'firstName'], ['fullName']],
            'nothing by partial parameters - firstName only' => [['firstName'], []],
            'nothing by partial parameters - surname only' => [['surname'], []],
            'adult' => [['ageFrom'], ['adult']],
        ];
    }
}
