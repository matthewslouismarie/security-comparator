<?php

namespace App\Service;

use App\Entity\Member;
use App\Entity\U2FToken;
use Doctrine\ORM\EntityManagerInterface;
use Firehed\U2F\SignResponse;

class AuthRequestService
{
    private $em;
    private $server;
    private $session;

    public function __construct(EntityManagerInterface $em,
                                U2FService $u2f, SecureSessionService $session)
    {
        $this->em = $em;
        $this->server = $u2f->getServer();
        $this->session = $session;
    }

    public function generate(string $username): array
    {
        $member = $this->em
                       ->getRepository(Member::class)
                       ->findOneBy(array('username' => $username));

        $registrations = $this->em
                              ->getRepository(U2FToken::class)
                              ->getMemberRegistrations($member->getId());

        $sign_requests = $this->server->generateSignRequests($registrations);
        $auth_id = $this->session->store(serialize($sign_requests));

        return array(
            'sign_requests_json' => json_encode(array_values($sign_requests)),
            'username' => $username,
            'auth_id' => $auth_id
        );
    }

    /**
     * @todo sql transaction
     */
    public function processResponse(string $auth_id, string $username, string $token_response)
    {
        $member = $this->em
                       ->getRepository(Member::class)
                       ->findOneBy(array('username' => $username));

        $registrations = $this->em
                              ->getRepository(U2FToken::class)
                              ->getMemberRegistrations($member->getId());

        $sign_requests = unserialize($this->session->getAndRemove($auth_id));
        $this->server
             ->setRegistrations($registrations)
             ->setSignRequests($sign_requests);
        $response = SignResponse::fromJson($token_response);
        $registration = $this->server->authenticate($response);

        $challenge = $response->getClientData()->getChallenge();
        $u2f_authenticator_id = $this->getAuthenticatorId($sign_requests, $challenge);
        
        $u2f_token = $this->em
                          ->getRepository(U2FToken::class)
                          ->find($u2f_authenticator_id);
        $u2f_token->setCounter($response->getCounter());
        $this->em->flush();
    }

    private function getAuthenticatorId(array $sign_requests, string $challenge): string
    {
        foreach ($sign_requests as $authenticator_id => $sign_request) {
            if ($sign_request->getChallenge() === $challenge) {
                return $authenticator_id;
            }
        }
    }
}