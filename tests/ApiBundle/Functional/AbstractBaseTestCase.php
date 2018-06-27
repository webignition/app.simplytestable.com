<?php

namespace Tests\ApiBundle\Functional;

use Mockery\Mock;
use SimplyTestable\ApiBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class AbstractBaseTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();

        $this->container->get('doctrine')->getConnection()->beginTransaction();
    }

    /**
     * @param User $user
     */
    protected function setUser(User $user)
    {
        $securityTokenStorage = $this->container->get('security.token_storage');

        /* @var Mock|TokenInterface $token */
        $token = \Mockery::mock(TokenInterface::class);
        $token
            ->shouldReceive('getUser')
            ->andReturn($user);

        $securityTokenStorage->setToken($token);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        if (!is_null($this->container)) {
            $this->container->get('doctrine')->getConnection()->close();
        }

        $refl = new \ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }
}
