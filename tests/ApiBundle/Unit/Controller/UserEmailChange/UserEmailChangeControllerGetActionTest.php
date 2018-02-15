<?php

namespace Tests\ApiBundle\Unit\Controller\UserEmailChange;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\Mock;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\MockFactory;

/**
 * @group Controller/UserEmailChangeController
 */
class UserEmailChangeControllerGetActionTest extends AbstractUserEmailChangeControllerTest
{
    public function testGetActionInvalidUser()
    {
        $userEmailChangeController = $this->createUserEmailChangeController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => 'foo@example.com',
                    'return' => null,
                ],
            ]),
        ]);

        $response = $userEmailChangeController->getAction('foo@example.com');

        $this->assertEquals([], json_decode($response->getContent()));
    }

    public function testGetActionNoEmailChangeRequest()
    {
        $user = new User();

        /* @var Mock|EntityRepository $emailChangeRequestRepository */
        $emailChangeRequestRepository = \Mockery::mock(EntityRepository::class);
        $emailChangeRequestRepository
            ->shouldReceive('findOneBy')
            ->with([
                'user' => $user,
            ])
            ->andReturnNull();

        $userEmailChangeController = $this->createUserEmailChangeController([
            UserService::class => MockFactory::createUserService([
                'findUserByEmail' => [
                    'with' => 'foo@example.com',
                    'return' => $user,
                ],
            ]),
            EntityManagerInterface::class => MockFactory::createEntityManager([
                'getRepository' => [
                    'with' => UserEmailChangeRequest::class,
                    'return' => $emailChangeRequestRepository,
                ],
            ]),
        ]);

        $response = $userEmailChangeController->getAction('foo@example.com');

        $this->assertEquals([], json_decode($response->getContent()));
    }
}
