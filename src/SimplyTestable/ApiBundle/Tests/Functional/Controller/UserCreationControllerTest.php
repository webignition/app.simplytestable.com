<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\UserCreationController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserCreationControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserCreationController
     */
    private $userCreationController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userCreationController = new UserCreationController();
        $this->userCreationController->setContainer($this->container);
    }

    public function testCreateRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('usercreation_create');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'email' => 'foo-user@example.com',
                'password' => 'foo-password',
            ],
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testActivateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $response = $this->userCreationController->activateAction(new Request());
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY);

        $response = $this->userCreationController->activateAction(new Request());
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $response = $this->userCreationController->createAction(new Request());
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY);

        $response = $this->userCreationController->createAction(new Request());
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    /**
     * @dataProvider createActionBadRequestDataProvider
     *
     * @param string $email
     * @param string $password
     * @param string $expectedExceptionMessage
     */
    public function testCreateActionBadRequest($email, $password, $expectedExceptionMessage)
    {
        $request = new Request([], [
            'email' => $email,
            'password' => $password,
        ]);

        $this->setExpectedException(
            BadRequestHttpException::class,
            $expectedExceptionMessage
        );

        $this->userCreationController->createAction($request);
    }

    /**
     * @return array
     */
    public function createActionBadRequestDataProvider()
    {
        return [
            'no email no password' => [
                'email' => null,
                'password' => null,
                'expectedExceptionMessage' => '"email" missing',
            ],
            'has email no password' => [
                'email' => 'user@example.com',
                'password' => null,
                'expectedExceptionMessage' => '"password" missing',
            ],
            'no email has password' => [
                'email' => null,
                'password' => 'password',
                'expectedExceptionMessage' => '"email" missing',
            ],
        ];
    }

    public function testCreateActionUserAlreadyActivated()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $request = new Request([], [
            'email' => $user->getEmail(),
            'password' => 'not relevant',
        ]);

        /* @var RedirectResponse $response */
        $response = $this->userCreationController->createAction($request);

        $this->assertTrue($response instanceof RedirectResponse);

        $this->assertEquals(
            'http://localhost/user/user@example.com/',
            $response->getTargetUrl()
        );
    }

    public function testCreateActionExistingUserPasswordIsChanged()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $initialPassword = $user->getPassword();

        $request = new Request([], [
            'email' => $user->getEmail(),
            'password' => 'different password',
        ]);

        /* @var RedirectResponse $response */
        $response = $this->userCreationController->createAction($request);

        $this->assertTrue($response->isSuccessful());

        $user = $userService->findUserByEmail($user->getEmail());

        $this->assertNotEquals($initialPassword, $user->getPassword());
    }

    /**
     * @dataProvider createActionDataProvider
     *
     * @param bool $createUser
     * @param string $email
     * @param string $password
     * @param string $coupon
     * @param string $plan
     * @param string $expectedPlanName
     * @param array $expectedPostActivationProperties
     */
    public function testCreateAction(
        $createUser,
        $email,
        $password,
        $coupon,
        $plan,
        $expectedPlanName,
        $expectedPostActivationProperties
    ) {
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $userPostActivationPropertiesService = $this->container->get(
            'simplytestable.services.job.userpostactivationpropertiesservice'
        );

        if ($createUser) {
            $userFactory = new UserFactory($this->container);
            $userFactory->create();
        }

        $requestData = [
            'email' => $email,
            'password' => $password,
        ];

        if (!empty($coupon)) {
            $requestData['coupon'] = $coupon;
        }

        if (!empty($plan)) {
            $requestData['plan'] = $plan;
        }

        $request = new Request([], $requestData);

        /* @var RedirectResponse $response */
        $response = $this->userCreationController->createAction($request);

        $this->assertTrue($response->isSuccessful());

        $user = $userService->findUserByEmail(rawurldecode($email));

        $this->assertInstanceOf(User::class, $user);

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $postActivationProperties = $userPostActivationPropertiesService->getForUser($user);

        if (is_null($expectedPlanName)) {
            $this->assertNull($userAccountPlan);
        } else {
            $this->assertEquals($expectedPlanName, $userAccountPlan->getPlan()->getName());
        }

        if (is_null($expectedPostActivationProperties)) {
            $this->assertNull($postActivationProperties);
        } else {
            $this->assertEquals(
                $expectedPostActivationProperties['planName'],
                $postActivationProperties->getAccountPlan()->getName()
            );

            $this->assertEquals(
                $expectedPostActivationProperties['coupon'],
                $postActivationProperties->getCoupon()
            );
        }
    }

    /**
     * @return array
     */
    public function createActionDataProvider()
    {
        return [
            'new user' => [
                'createUser' => false,
                'email' => 'user@example.com',
                'password' => 'user password',
                'coupon' => null,
                'plan' => null,
                'expectedPlanName' => 'basic',
                'expectedPostActivationProperties' => null,
            ],
            'new user; values encoded' => [
                'createUser' => false,
                'email' => rawurlencode('user@example.com'),
                'password' => rawurlencode('user password'),
                'coupon' => null,
                'plan' => null,
                'expectedPlanName' => 'basic',
                'expectedPostActivationProperties' => null,
            ],
            'existing user not enabled' => [
                'createUser' => true,
                'email' => 'user@example.com',
                'password' => 'user password',
                'coupon' => null,
                'plan' => null,
                'expectedPlanName' => 'basic',
                'expectedPostActivationProperties' => null,
            ],
            'plan not valid' => [
                'createUser' => true,
                'email' => 'user@example.com',
                'password' => 'user password',
                'coupon' => null,
                'plan' => 'foo',
                'expectedPlanName' => 'basic',
                'expectedPostActivationProperties' => null,
            ],
            'premium plan, no coupon' => [
                'createUser' => true,
                'email' => 'agency@example.com',
                'password' => 'user password',
                'coupon' => null,
                'plan' => 'agency',
                'expectedPlanName' => null,
                'expectedPostActivationProperties' => [
                    'planName' => 'agency',
                    'coupon' => null,
                ],
            ],
            'premium plan, has coupon' => [
                'createUser' => true,
                'email' => 'agency@example.com',
                'password' => 'user password',
                'coupon' => 'foo-coupon',
                'plan' => 'business',
                'expectedPlanName' => null,
                'expectedPostActivationProperties' => [
                    'planName' => 'business',
                    'coupon' => 'foo-coupon',
                ],
            ],
        ];
    }

    /**
     * @dataProvider activateActionEmptyTokenDataProvider
     *
     * @param string $token
     */
    public function testActivateActionEmptyToken($token)
    {
        $this->setExpectedException(BadRequestHttpException::class);

        $this->userCreationController->activateAction($token);
    }

    /**
     * @return array
     */
    public function activateActionEmptyTokenDataProvider()
    {
        return [
            'null' => [
                'token' => null,
            ],
            'empty string' => [
                'token' => '',
            ],
            'whitespace string' => [
                'token' => ' ',
            ],
        ];
    }

    public function testActivateActionInvalidToken()
    {
        $this->setExpectedException(BadRequestHttpException::class);

        $this->userCreationController->activateAction('foo');
    }

    public function testActivateActionSuccessNoPostActivationProperties()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $response = $this->userCreationController->activateAction($user->getConfirmationToken());

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($user->isEnabled());
    }

    public function testActivateActionSuccessHasPostActivationProperties()
    {
        $userPostActivationPropertiesService = $this->container->get(
            'simplytestable.services.job.userpostactivationpropertiesservice'
        );

        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        $agencyAccountPlan = $accountPlanService->find('agency');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $postActivationProperties = $userPostActivationPropertiesService->create($user, $agencyAccountPlan, 'TMS');

        $response = $this->userCreationController->activateAction($user->getConfirmationToken());

        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($user->isEnabled());

        $userAccountPlan = $userAccountPlanService->getForUser($user);

        $this->assertEquals(
            $postActivationProperties->getAccountPlan(),
            $userAccountPlan->getPlan()
        );

        $this->assertEmpty($postActivationProperties->getId());
    }
}
