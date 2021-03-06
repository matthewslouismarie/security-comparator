<?php

declare(strict_types=1);

namespace App\Tests;

use App\DataFixtures\AppFixture;
use App\Enum\SecurityStrategy;
use App\Enum\Setting;
use App\Service\Form\Filler\CredentialAuthenticationFiller;
use App\Service\Form\Filler\ExistingUsernameFiller;
use App\Service\Form\Filler\U2fAuthenticationFiller;
use LM\AuthAbstractor\Model\AuthenticationProcess;
use LM\Common\Model\ArrayObject;

class LoginTest extends TestCaseTemplate
{
    use LoginTrait;

    public function testLogin()
    {
        $this
            ->getAppConfigManager()
            ->set(Setting::ALLOW_U2F_LOGIN, true)
            ->set(Setting::ALLOW_PWD_LOGIN, false)
            ->set(Setting::SECURITY_STRATEGY, SecurityStrategy::U2F)
            ->set(Setting::N_U2F_KEYS_LOGIN, 1)
        ;
        $this->doGet("/not-authenticated/login/u2f");
        $this->assertIsRedirect();
        $this->followRedirect();
        $this->submit($this
            ->get(CredentialAuthenticationFiller::class)
            ->fillForm($this->getCrawler(), AppFixture::ADMIN_PASSWORD.'lol', AppFixture::ADMIN_USERNAME.'eui'))
        ;
        $this->assertContains('Invalid credentials', $this->getResponseContent());
        $this->submit($this
            ->get(CredentialAuthenticationFiller::class)
            ->fillForm($this->getCrawler(), AppFixture::ADMIN_PASSWORD, AppFixture::ADMIN_USERNAME))
        ;
        $this->assertNotContains(
            'This form should not contain extra fields.',
            $this->getClient()->getResponse()->getContent()
        )
        ;
        $this->submit($this
            ->get(U2fAuthenticationFiller::class)
            ->fillForm($this->getCrawler(), $this->getUriLastPart(), false))
        ;
        $this->assertNotContains(
            'There was an error. Please try again.',
            $this->getClient()->getResponse()->getContent()
        )
        ;
        $this->submit($this
            ->get(U2fAuthenticationFiller::class)
            ->fillForm($this->getCrawler(), $this->getUriLastPart()))
        ;
        $this->assertTrue($this->isAuthenticatedFully());
        $this->assertContains('You logged in successfully.', $this->getResponseContent());
    }

    public function testLoginWithTwoU2fKeys()
    {
        $this
            ->getAppConfigManager()
            ->set(Setting::ALLOW_U2F_LOGIN, true)
            ->set(Setting::ALLOW_PWD_LOGIN, false)
            ->set(Setting::N_U2F_KEYS_LOGIN, 2)
        ;
        $this->doGet("/not-authenticated/tmp-login");
        $this->followRedirect();
        $this->submit($this
            ->get(ExistingUsernameFiller::class)
            ->fillForm($this->getCrawler(), AppFixture::ADMIN_USERNAME.'euieu'))
        ;
        $this->assertContains('The username is invalid.', $this->getResponseContent());
        $this->submit($this
            ->get(ExistingUsernameFiller::class)
            ->fillForm($this->getCrawler(), AppFixture::ADMIN_USERNAME))
        ;
        
        $this->assertSame(
            0,
            $this
                ->getSecureSession()
                ->getObject($this->getUriLastPart(), AuthenticationProcess::class)
                ->getTypedMap()
                ->get('used_u2f_key_public_keys', ArrayObject::class)
                ->getSize()
        )
        ;
        $this->submit($this
            ->get(U2fAuthenticationFiller::class)
            ->fillForm($this->getCrawler(), $this->getUriLastPart()))
        ;
        $this->assertSame(
            1,
            $this
                ->getSecureSession()
                ->getObject($this->getUriLastPart(), AuthenticationProcess::class)
                ->getTypedMap()
                ->get('used_u2f_key_public_keys', ArrayObject::class)
                ->getSize()
        )
        ;
        $this->submit($this
            ->get(U2fAuthenticationFiller::class)
            ->fillForm($this->getCrawler(), $this->getUriLastPart()))
        ;
        $this->assertContains('The U2F key is not recognised.', $this
            ->getClient()
            ->getResponse()
            ->getContent())
        ;
    }

    public function testCredentialLogin()
    {
        $this
            ->getAppConfigManager()
            ->set(Setting::ALLOW_PWD_LOGIN, true)
            ->set(Setting::ALLOW_U2F_LOGIN, false)
        ;
        $this->doGet("/not-authenticated/choose-authenticate");
        $this->assertIsRedirect();
        $this->followRedirect();
        $this->assertSame(
            'http://localhost/not-authenticated/login/pwd',
            $this->getUri()
        )
        ;
        $this->assertIsRedirect();
        $this->followRedirect();
        $this->submit($this
            ->get(CredentialAuthenticationFiller::class)
            ->fillForm($this->getCrawler(), AppFixture::ADMIN_PASSWORD, AppFixture::ADMIN_USERNAME.'eiueui'))
        ;
        $this->assertContains('Invalid credentials', $this->getResponseContent());
        $this->submit($this
            ->get(CredentialAuthenticationFiller::class)
            ->fillForm($this->getCrawler(), AppFixture::ADMIN_PASSWORD, AppFixture::ADMIN_USERNAME))
        ;
        $this->assertTrue($this->isAuthenticatedFully());
        $this->assertContains('You logged in successfully.', $this->getResponseContent());
    }
}
