<?php

namespace App\Tests\Functional;

use Mockery\Mock;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class AbstractBaseTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->client = static::createClient();
    }

    /**
     * @param User $user
     */
    protected function setUser(User $user)
    {
        $securityTokenStorage = self::$container->get('security.token_storage');

        /* @var Mock|TokenInterface $token */
        $token = \Mockery::mock(TokenInterface::class);
        $token
            ->shouldReceive('getUser')
            ->andReturn($user);

        $securityTokenStorage->setToken($token);
    }
}
