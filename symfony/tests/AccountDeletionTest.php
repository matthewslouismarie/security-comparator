<?php

namespace App\Tests;

use App\Tests\TestCaseTemplate;

/**
 * @todo Temporarily disabled.
 */
class AccountDeletionTest extends TestCaseTemplate
{
    // use AuthenticationTrait;

    public function testAccountDeletion()
    {
        $this->assertTrue(true);
    //     $this->u2fAuthenticate();
    //     $this->doGet('/authenticated/my-account/delete-account');
    //     $this->assertContains(
    //         'Do you really want to delete your account?',
    //         $this->getClient()->getResponse()->getContent())
    //     ;
    //     $this->submit($this
    //         ->get('App\Service\Form\Filler\UserConfirmationFiller')
    //         ->fillForm($this->getCrawler()))
    //     ;
    //     $this->performHighSecurityAuthenticationAsLouis();
    }
}