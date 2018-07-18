<?php

namespace Tests\AppBundle\Unit\Controller\UserEmailChange;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\Mock;
use AppBundle\Entity\User;
use AppBundle\Entity\UserEmailChangeRequest;
use AppBundle\Services\UserService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\AppBundle\Factory\MockFactory;

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

        $this->expectException(NotFoundHttpException::class);

        $userEmailChangeController->getAction('foo@example.com');
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

        $this->expectException(NotFoundHttpException::class);

        $userEmailChangeController->getAction('foo@example.com');
    }
}
