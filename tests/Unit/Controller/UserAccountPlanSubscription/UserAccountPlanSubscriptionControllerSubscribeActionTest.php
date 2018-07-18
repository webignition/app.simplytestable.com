<?php

namespace App\Tests\Unit\Controller\UserAccountPlanSubscription;

use App\Entity\User;
use App\Services\AccountPlanService;
use App\Services\ApplicationStateService;
use App\Services\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Tests\Factory\MockFactory;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Tests\Factory\ModelFactory;

/**
 * @group Controller/UserAccountPlanSubscriptionController
 */
class UserAccountPlanSubscriptionControllerSubscribeActionTest extends AbstractUserAccountPlanSubscriptionControllerTest
{
    public function testSubscribeActionInMaintenanceReadOnlyMode()
    {
        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $userAccountPlanSubscriptionController->subscribeAction(
            new User(),
            'user@example.com',
            'plan name'
        );
    }

    public function testSubscribeActionPublicUser()
    {
        $user = new User();

        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => true,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userAccountPlanSubscriptionController->subscribeAction(
            $user,
            $user->getEmail(),
            'plan name'
        );
    }

    public function testAssociateCardActionInvalidUser()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => false,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userAccountPlanSubscriptionController->subscribeAction(
            $user,
            'foo@example.com',
            'plan name'
        );
    }

    public function testAssociateCardActionInvalidPlan()
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $userAccountPlanSubscriptionController = $this->createUserAccountPlanSubscriptionController([
            UserService::class => MockFactory::createUserService([
                'isPublicUser' => [
                    'with' => $user,
                    'return' => false,
                ],
            ]),
            AccountPlanService::class => MockFactory::createAccountPlanService([
                'get' => [
                    'with' => 'plan name',
                    'return' => null,
                ],
            ]),
        ]);

        $this->expectException(BadRequestHttpException::class);

        $userAccountPlanSubscriptionController->subscribeAction(
            $user,
            $user->getEmail(),
            'plan name'
        );
    }
}
