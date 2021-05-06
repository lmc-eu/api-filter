<?php

declare(strict_types=1);

use Lmc\CodingStandard\Sniffs\Naming\ClassNameSuffixByParentSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\OperatorSpacingSniff;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(
        Option::SKIP,
        [
            'SlevomatCodingStandard\Sniffs\Exceptions\ReferenceThrowableOnlySniff.ReferencedGeneralException' => ['tests/Exception/*.php'],
            OperatorSpacingSniff::class => null,
            BinaryOperatorSpacesFixer::class => null,
        ]
    );

    $containerConfigurator->import(__DIR__ . '/vendor/lmc/coding-standard/ecs.php');

    $services = $containerConfigurator->services();

    $services->set(PhpUnitTestAnnotationFixer::class)
        ->call('configure', [['style' => 'annotation']]);

    $services->set(ClassNameSuffixByParentSniff::class)
        ->property('extraParentTypesToSuffixes', ['*ApplicatorInterface' => 'Applicator']);

    $services->set(NoSuperfluousPhpdocTagsFixer::class)
        ->call('configure', [['allow_mixed' => true]]);
};
