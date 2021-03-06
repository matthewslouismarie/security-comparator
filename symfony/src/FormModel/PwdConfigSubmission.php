<?php

declare(strict_types=1);

namespace App\FormModel;

use Symfony\Component\Validator\Constraints as Assert;

class PwdConfigSubmission
{
    /**
     * @Assert\Type("bool")
     */
    public $allowPwdAuthentication;

    /**
     * @Assert\Type("integer")
     */
    public $minimumLength;

    /**
     * @Assert\Type("bool")
     */
    public $enforceMinimumLength;

    /**
     * @Assert\Type("bool")
     */
    public $requireNumbers;

    /**
     * @Assert\Type("bool")
     */
    public $requireSpecialCharacters;

    /**
     * @Assert\Type("bool")
     */
    public $requireUppercaseLetters;

    /**
     * @Assert\Type("bool")
     */
    public $forceComplexPasswords;

    public function __construct(
        ?bool $allowPwdAuthentication = null,
        ?int $minimumLength = null,
        ?bool $enforceMinimumLength = null,
        ?bool $requireNumbers = null,
        ?bool $requireSpecialCharacters = null,
        ?bool $requireUppercaseLetters = null
    ) {
        $this->allowPwdAuthentication = $allowPwdAuthentication;
        $this->minimumLength = $minimumLength;
        $this->enforceMinimumLength = $enforceMinimumLength;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialCharacters = $requireSpecialCharacters;
        $this->requireUppercaseLetters = $requireUppercaseLetters;
    }
}
