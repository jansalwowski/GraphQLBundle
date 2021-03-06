<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Validator\Constraints;

use Overblog\GraphQLBundle\Validator\ValidationNode;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ExpressionValidator extends \Symfony\Component\Validator\Constraints\ExpressionValidator
{
    private $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
        parent::__construct(null, $expressionLanguage);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Expression) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Expression');
        }

        $variables = $constraint->values;
        $variables['value'] = $value;

        $object = $this->context->getObject();

        $variables['this'] = $object;

        if ($object instanceof ValidationNode) {
            $variables['parentValue'] = $object->getResolverArg('value');
            $variables['context'] = $object->getResolverArg('context');
            $variables['args'] = $object->getResolverArg('args');
            $variables['info'] = $object->getResolverArg('info');
        }

        if (!$this->expressionLanguage->evaluate($constraint->expression, $variables)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value, self::OBJECT_TO_STRING))
                ->setCode(Expression::EXPRESSION_FAILED_ERROR)
                ->addViolation();
        }
    }
}
