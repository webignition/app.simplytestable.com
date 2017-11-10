<?php

namespace Tests\ApiBundle\Functional\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\JobConfigurationController;
use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class JobConfigurationControllerCreateActionTest extends AbstractBaseTestCase
{
    /**
     * @var JobConfigurationController
     */
    private $jobConfigurationController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationController = new JobConfigurationController();
        $this->jobConfigurationController->setContainer($this->container);
    }

    public function testCreateActionPostRequest()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $router = $this->container->get('router');
        $requestUrl = $router->generate('jobconfiguration_create');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'label' => 'label',
                'website' => 'website value',
                'type' => 'type value',
                'task-configuration' => [
                    'HTML Validation' => [],
                ],
            ],
            'user' => $user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertTrue($response->isRedirect('/jobconfiguration/label/'));
    }

    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->jobConfigurationController->createAction(new Request());
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
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

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->jobConfigurationController->createAction($request);
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
        $userService = $this->container->get(UserService::class);

        $user = $userService->findUserByEmail($userEmail);
        $this->setUser($user);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $response = $this->jobConfigurationController->createAction($request);

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
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $this->setUser($user);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $this->jobConfigurationController->createAction($request);
        $response = $this->jobConfigurationController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":2,"message":"Label \"label value\" is not unique"}',
            $response->headers->get('x-jobconfigurationcreate-error')
        );
    }

    public function testCreateActionFailureHasExistingJobConfiguration()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $this->setUser($user);

        $request = new Request([], [
            'label' => 'label value',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $this->jobConfigurationController->createAction($request);

        $request->request->set('label', 'different label value');

        $response = $this->jobConfigurationController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":3,"message":"Matching configuration already exists"}',
            $response->headers->get('x-jobconfigurationcreate-error')
        );
    }

    public function testCreateAction()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobConfigurationRepository = $entityManager->getRepository(Configuration::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $this->setUser($user);

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
        $response = $this->jobConfigurationController->createAction($request);

        $this->assertEquals(
            '/jobconfiguration/label%20value/',
            $response->getTargetUrl()
        );

        $jobConfiguration = $jobConfigurationRepository->findOneBy([
            'label' => $label,
        ]);

        $this->assertInstanceOf(JobConfiguration::class, $jobConfiguration);
    }
}
