<?php

declare(strict_types=1);

namespace Rowbot\URL\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\Php\RegexArrayShapeMatcher;
use PHPStan\Type\Type;
use Rowbot\URL\String\USVStringInterface;

final class PregMatchParameterOutExtension implements MethodParameterOutTypeExtension
{
    private RegexArrayShapeMatcher $regexShapeMatcher;

    public function __construct(RegexArrayShapeMatcher $regexShapeMatcher)
    {
        $this->regexShapeMatcher = $regexShapeMatcher;
    }

    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return
            $methodReflection->getDeclaringClass()->getName() === USVStringInterface::class
            && $methodReflection->getName() === 'matches'
            && $parameter->getName() === 'matches';
    }

    public function getParameterOutTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope
    ): ?Type {
        $args = $methodCall->getArgs();
        $patternArg = $args[0] ?? null;
        $matchesArg = $args[1] ?? null;
        $flagsArg = $args[2] ?? null;

        if ($patternArg === null || $matchesArg === null) {
            return null;
        }

        $flagsType = null;

        if ($flagsArg !== null) {
            $flagsType = $scope->getType($flagsArg->value);
        }

        return $this->regexShapeMatcher->matchExpr($patternArg->value, $flagsType, TrinaryLogic::createMaybe(), $scope);
    }
}
