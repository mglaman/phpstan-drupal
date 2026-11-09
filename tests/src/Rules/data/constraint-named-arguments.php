<?php

namespace ConstraintNamedArguments;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;
use Symfony\Component\Validator\Constraints\Choice;

#[Constraint(id: 'NoConstructor', label: new TranslatableMarkup('No constructor'))]
final class NoConstructorConstraint extends SymfonyConstraint {
    public string $message = 'The value is not valid.';
    public int $max = 10;
}

/**
 * @Constraint(
 *   id = "AnnotatedNoConstructor",
 *   label = @Translation("Annotated without constructor")
 * )
 */
final class AnnotatedNoConstructorConstraint extends SymfonyConstraint {
    public string $message = 'The value is not valid.';
}

#[Constraint(id: 'ArrayConstructor', label: new TranslatableMarkup('Array constructor'))]
final class ArrayConstructorConstraint extends SymfonyConstraint {
    public int $max = 10;

    public function __construct(array $options = []) {
        parent::__construct($options);
    }
}

#[Constraint(id: 'MissingParameter', label: new TranslatableMarkup('Missing parameter'))]
final class MissingParameterConstraint extends SymfonyConstraint {
    public string $message = 'The value is not valid.';

    #[HasNamedArguments]
    public function __construct(public int $max = 10, ?array $groups = NULL, mixed $payload = NULL) {
        parent::__construct(NULL, $groups, $payload);
    }
}

// Not reported: the constructor takes every option as a named argument.
#[Constraint(id: 'NamedArguments', label: new TranslatableMarkup('Named arguments'))]
final class NamedArgumentsConstraint extends SymfonyConstraint {
    #[HasNamedArguments]
    public function __construct(
        public string $message = 'The value is not valid.',
        public int $max = 10,
        ?array $groups = NULL,
        mixed $payload = NULL,
    ) {
        parent::__construct(NULL, $groups, $payload);
    }
}

// Not reported: Symfony's Choice constraint has a named-arguments constructor.
#[Constraint(id: 'ChoiceSubclass', label: new TranslatableMarkup('Choice subclass'))]
final class ChoiceSubclassConstraint extends Choice {
}

// Not reported: a constraint without options.
#[Constraint(id: 'NoOptions', label: new TranslatableMarkup('No options'))]
final class NoOptionsConstraint extends SymfonyConstraint {
}

// Not reported: not a Drupal plugin.
final class NotAPluginConstraint extends SymfonyConstraint {
    public int $max = 10;
}
