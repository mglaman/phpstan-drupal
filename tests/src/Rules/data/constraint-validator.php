<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules\data\ConstraintValidator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

// Constraint class following the naming convention: UniqueItem / UniqueItemValidator.
class UniqueItem extends Constraint
{

    public string $alreadyExists = 'The item %value already exists.';

}

// Error: validate() accesses constraint properties but never asserts the type.
class UniqueItemValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        $this->context->addViolation($constraint->alreadyExists);
    }

}

// No error: validate() uses assert() to narrow the constraint type.
class UniqueItemValidatorWithAssert extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        assert($constraint instanceof UniqueItem);
        $this->context->addViolation($constraint->alreadyExists);
    }

}

// No error: validator class name does not follow the FooValidator/Foo convention,
// so no matching constraint class can be derived.
class StandaloneValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        $this->context->addViolation('some message');
    }

}

// No error: 'OrphanedValidator' strips to 'Orphaned', which is not a known constraint.
class OrphanedValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        $this->context->addViolation('some message');
    }

}
