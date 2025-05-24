<?php

declare(strict_types=1);

namespace Rowbot\URL\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\Php\RegexArrayShapeMatcher;
use Rowbot\URL\String\USVStringInterface;

final class PregMatchTypeSpecifyingExtension implements MethodTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private RegexArrayShapeMatcher $regexShapeMatcher;

    private TypeSpecifier $typeSpecifier;

    public function __construct(RegexArrayShapeMatcher $regexShapeMatcher)
    {
        $this->regexShapeMatcher = $regexShapeMatcher;
    }

    public function getClass(): string
    {
        return USVStringInterface::class;
    }

    public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
    {
        $this->typeSpecifier = $typeSpecifier;
    }

    public function isMethodSupported(MethodReflection $methodReflection, MethodCall $node, TypeSpecifierContext $context): bool
    {
        return $methodReflection->getName() === 'matches' && !$context->null();
    }

    public function specifyTypes(MethodReflection $methodReflection, MethodCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
    {
        $args = $node->getArgs();
        $patternArg = $args[0] ?? null;
        $matchesArg = $args[1] ?? null;
        $flagsArg = $args[2] ?? null;

        if ($patternArg === null || $matchesArg === null) {
            return new SpecifiedTypes();
        }

        $flagsType = null;

        if (null !== $flagsArg) {
            $flagsType = $scope->getType($flagsArg->value);
        }

        $matchedType = $this->regexShapeMatcher->matchExpr(
            $patternArg->value,
            $flagsType,
            TrinaryLogic::createFromBoolean($context->true()),
            $scope
        );

        if ($matchedType === null) {
            return new SpecifiedTypes();
        }

        $overwrite = false;

		if ($context->false()) {
			$overwrite = true;
			$context = $context->negate();
		}

		$types = $this->typeSpecifier->create(
			$matchesArg->value,
			$matchedType,
			$context,
			$scope,
		)->setRootExpr($node);

		if ($overwrite) {
			$types = $types->setAlwaysOverwriteTypes();
		}

		return $types;
    }
}
