<?php

declare(strict_types=1);

namespace mglaman\PHPStanDrupal\Tests\Rules\data\ConstraintValidator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

// Constraint class following the naming convention: UniqueItem / UniqueItemValidator.
class UniqueItem extends Constraint
{

    public string $alreadyExists = 'The item %value already exists.';

}

// Error: validate() does not assert the concrete constraint type.
class UniqueItemValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        $this->context->addViolation($constraint->alreadyExists);
    }

}

// Constraint class for the assert-detection test case.
class UniqueItemWithAssert extends Constraint
{

    public string $alreadyExists = 'The item %value already exists.';

}

// No error: validate() uses assert() to narrow to the expected constraint type.
class UniqueItemWithAssertValidator extends ConstraintValidator
{

    public function validate(mixed $value, Constraint $constraint): void
    {
        assert($constraint instanceof UniqueItemWithAssert);
        $this->context->addViolation($constraint->alreadyExists);
    }

}

// No error: 'StandaloneValidator' strips to 'Standalone', but there is no
// corresponding constraint class with that name.
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
