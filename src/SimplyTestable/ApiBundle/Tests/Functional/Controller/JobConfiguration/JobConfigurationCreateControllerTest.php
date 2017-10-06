<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\CreateController;
use SimplyTestable\ApiBundle\Controller\UserCreationController;
use SimplyTestable\ApiBundle\Entity\Job\Type;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;

class JobConfigurationCreateControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var CreateController
     */
    private $jobConfigurationCreateController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationCreateController = new CreateController();
        $this->jobConfigurationCreateController->setContainer($this->container);
    }

//    public function testRequest()
//    {
//        $router = $this->container->get('router');
//        $requestUrl = $router->generate('usercreation_create');
//
//        $this->getCrawler([
//            'url' => $requestUrl,
//            'method' => 'POST',
//            'parameters' => [
//                'email' => 'foo-user@example.com',
//                'password' => 'foo-password',
//            ],
//        ]);
//
//        /* @var RedirectResponse $response */
//        $response = $this->getClientResponse();
//
//        $this->assertTrue($response->isSuccessful());
//    }

    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $response = $this->jobConfigurationCreateController->createAction(new Request());
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY);

        $response = $this->jobConfigurationCreateController->createAction(new Request());
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    /**
     * @dataProvider createActionBadRequestDataProvider
     *
     * @param string $label
     * @param string $website
     * @param string $type
     * @param array $taskConfiguration
     * @param string $expectedExceptionMessage
     */
    public function testCreateActionBadRequest($label, $website, $type, $taskConfiguration, $expectedExceptionMessage)
    {
        $request = new Request([], [
            'label' => $label,
            'website' => $website,
            'type' => $type,
            'task-configuration' => $taskConfiguration,
        ]);

        $this->setExpectedException(
            BadRequestHttpException::class,
            $expectedExceptionMessage
        );

        $this->jobConfigurationCreateController->createAction($request);
    }

    /**
     * @return array
     */
    public function createActionBadRequestDataProvider()
    {
        return [
            'label missing' => [
                'label' => null,
                'website' => null,
                'type' => null,
                'taskConfiguration' => null,
                'expectedExceptionMessage' => '"label" missing',
            ],
            'website missing' => [
                'label' => 'label value',
                'website' => null,
                'type' => null,
                'taskConfiguration' => null,
                'expectedExceptionMessage' => '"website" missing',
            ],
            'type missing' => [
                'label' => 'label value',
                'website' => 'http://example.com',
                'type' => null,
                'taskConfiguration' => null,
                'expectedExceptionMessage' => '"type" missing',
            ],
            'task-configuration missing' => [
                'label' => 'label value',
                'website' => 'http://example.com',
                'type' => 'full site',
                'taskConfiguration' => null,
                'expectedExceptionMessage' => '"task-configuration" missing',
            ],
        ];
    }

    /**
     * @dataProvider createActionSpecialUserDataProvider
     *
     * @param string $userEmail
     */
    public function testCreateActionSpecialUser($userEmail)
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $user = $userService->findUserByEmail($userEmail);
        $userService->setUser($user);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $response = $this->jobConfigurationCreateController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":99,"message":"Special users cannot create job configurations"}',
            $response->headers->get('x-jobconfigurationcreate-error')
        );
    }

    /**
     * @return array
     */
    public function createActionSpecialUserDataProvider()
    {
        return [
            'public' => [
                'userEmail' => 'public@simplytestable.com',
            ],
            'admin' => [
                'userEmail' => 'admin@simplytestable.com',
            ],
        ];
    }

    public function testCreateActionFailureLabelNotUnique()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $userService->setUser($user);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $this->jobConfigurationCreateController->createAction($request);
        $response = $this->jobConfigurationCreateController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":2,"message":"Label \"label value\" is not unique"}',
            $response->headers->get('x-jobconfigurationcreate-error')
        );
    }

    public function testCreateActionFailureHasExistingJobConfiguration()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $userService->setUser($user);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $this->jobConfigurationCreateController->createAction($request);

        $request->request->set('label', 'different label value');

        $response = $this->jobConfigurationCreateController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":3,"message":"Matching configuration already exists"}',
            $response->headers->get('x-jobconfigurationcreate-error')
        );
    }

    public function testCreateAction()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $userService = $this->container->get('simplytestable.services.userservice');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $userService->setUser($user);

        $label = 'label value';

        $request = new Request([], [
            'label' => $label,
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        /* @var RedirectResponse $response */
        $response = $this->jobConfigurationCreateController->createAction($request);

        $this->assertEquals(
            '/jobconfiguration/label%20value/',
            $response->getTargetUrl()
        );

        $jobConfigurationRepository = $entityManager->getRepository(JobConfiguration::class);

        $jobConfiguration = $jobConfigurationRepository->findOneBy([
            'label' => $label,
        ]);

        $this->assertInstanceOf(JobConfiguration::class, $jobConfiguration);
    }
}
