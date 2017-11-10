<?php

namespace Tests\ApiBundle\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\JobConfigurationController;
use SimplyTestable\ApiBundle\Controller\ScheduledJobController;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ScheduledJobControllerCreateActionTest extends AbstractBaseTestCase
{
    /**
     * @var ScheduledJobController
     */
    private $scheduledJobController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->scheduledJobController = new ScheduledJobController();
        $this->scheduledJobController->setContainer($this->container);
    }

    public function testCreateActionPostRequest()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();
        $this->setUser($user);

        $this->createJobConfiguration();

        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_create');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'job-configuration' => 'job-configuration-label',
                'schedule' => '* * * * *',
            ],
            'user' => $user,
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        /* @var ScheduledJob $scheduledJob */
        $scheduledJob = $scheduledJobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('/scheduledjob/' . $scheduledJob->getId() . '/'));
    }

    public function testCreateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->scheduledJobController->createAction(new Request());
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    /**
     * @dataProvider createActionBadRequestDataProvider
     *
     * @param string $jobConfiguration
     * @param string $schedule
     * @param string $expectedExceptionMessage
     */
    public function testCreateActionBadRequest($jobConfiguration, $schedule, $expectedExceptionMessage)
    {
        $request = new Request([], [
            'job-configuration' => $jobConfiguration,
            'schedule' => $schedule,
        ]);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->scheduledJobController->createAction($request);
    }

    /**
     * @return array
     */
    public function createActionBadRequestDataProvider()
    {
        return [
            'job-configuration missing' => [
                'jobConfiguration' => null,
                'schedule' => null,
                'expectedExceptionMessage' => '"job-configuration" missing',
            ],
            'schedule missing' => [
                'jobConfiguration' => 'job configuration label',
                'schedule' => null,
                'expectedExceptionMessage' => '"schedule" missing',
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
            'job-configuration' => 'job configuration label',
            'schedule' => '* * * * *',
        ]);

        $response = $this->scheduledJobController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":99,"message":"Special users cannot create scheduled jobs"}',
            $response->headers->get('x-scheduledjobcreate-error')
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

    public function testCreateActionUnknownJobConfiguration()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();

        $this->setUser($user);

        $request = new Request([], [
            'job-configuration' => 'job configuration label',
            'schedule' => '* * * * *',
        ]);

        $response = $this->scheduledJobController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":98,"message":"Unknown job configuration \"job configuration label\""}',
            $response->headers->get('x-scheduledjobcreate-error')
        );
    }

    /**
     * @dataProvider createActionMalformedRequestDataProvider
     *
     * @param array $requestData
     * @param string $expectedResponseErrorHeader
     */
    public function testCreateActionMalformedRequest($requestData, $expectedResponseErrorHeader)
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $this->setUser($user);

        $this->createJobConfiguration();

        $request = new Request([], array_merge([
            'job-configuration' => 'job-configuration-label',
        ], $requestData));

        $response = $this->scheduledJobController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            $expectedResponseErrorHeader,
            $response->headers->get('x-scheduledjobcreate-error')
        );
    }

    /**
     * @return array
     */
    public function createActionMalformedRequestDataProvider()
    {
        return [
            'malformed schedule' => [
                'requestData' => [
                    'schedule' => 'foo',
                ],
                'expectedResponseErrorHeader' => '{"code":97,"message":"Malformed schedule \"foo\""}',
            ],
            'malformed schedule-modifier' => [
                'requestData' => [
                    'schedule' => '* * * * *',
                    'schedule-modifier' => 'foo',
                ],
                'expectedResponseErrorHeader' => '{"code":96,"message":"Malformed schedule modifier \"foo\""}',
            ],
        ];
    }

    public function testCreateActionFailureScheduleJobAlreadyExists()
    {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $this->setUser($user);

        $this->createJobConfiguration();

        $request = new Request([], [
            'job-configuration' => 'job-configuration-label',
            'schedule' => '* * * * *',
            'schedule-modifier' => null,
        ]);

        $this->scheduledJobController->createAction($request);
        $response = $this->scheduledJobController->createAction($request);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":2,"message":"Matching scheduled job exists"}',
            $response->headers->get('x-scheduledjobcreate-error')
        );
    }

    public function testCreateActionSuccess()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create();
        $this->setUser($user);

        $this->createJobConfiguration();

        $request = new Request([], [
            'job-configuration' => 'job-configuration-label',
            'schedule' => '* * * * *',
            'schedule-modifier' => '[ `date +\%d` -le 7 ]',
        ]);

        $response = $this->scheduledJobController->createAction($request);

        /* @var ScheduledJob $scheduledJob */
        $scheduledJob = $scheduledJobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('/scheduledjob/' . $scheduledJob->getId() . '/'));
    }

    private function createJobConfiguration()
    {
        $jobConfigurationCreateController = new JobConfigurationController();
        $jobConfigurationCreateController->setContainer($this->container);
        $jobConfigurationCreateController->createAction(new Request([], [
            'label' => 'job-configuration-label',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]));
    }
}
