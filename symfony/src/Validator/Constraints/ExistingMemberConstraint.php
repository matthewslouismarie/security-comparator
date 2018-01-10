<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ExistingMemberConstraint extends Constraint
{
    public $message = 'Your username or your password is not valid';
}