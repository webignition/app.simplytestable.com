<?php

namespace App\Tests\Functional\Controller\ScheduledJob;

use App\Controller\JobConfigurationController;
use App\Entity\ScheduledJob;
use App\Entity\User;
use App\Repository\ScheduledJobRepository;
use App\Services\Job\ConfigurationService;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Services\UserService;
use App\Services\WebSiteService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Factory\MockFactory;
use App\Tests\Services\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use App\Services\ScheduledJob\CronModifier\ValidationService as CronModifierValidationService;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerCreateActionTest extends AbstractScheduledJobControllerTest
{
    public function testCreateActionPostRequest()
    {
        $scheduledJobRepository = self::$container->get(ScheduledJobRepository::class);

        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->createAndActivateUser();
        $this->setUser($user);

        $this->createJobConfiguration($user);

        $router = self::$container->get('router');
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

        $response = $this->getClientResponse();

        /* @var ScheduledJob $scheduledJob */
        $scheduledJob = $scheduledJobRepository->findAll()[0];

        $this->assertTrue(
            $response->isRedirect('http://localhost/scheduledjob/' . $scheduledJob->getId() . '/')
        );
    }

    public function testCreateActionUnknownJobConfiguration()
    {
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create();

        $this->setUser($user);

        $request = new Request([], [
            'job-configuration' => 'job configuration label',
            'schedule' => '* * * * *',
        ]);

        $response = $this->callCreateAction($request, $user);

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
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create();
        $this->setUser($user);

        $this->createJobConfiguration($user);

        $request = new Request([], array_merge([
            'job-configuration' => 'job-configuration-label',
        ], $requestData));

        $response = $this->callCreateAction($request, $user);

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
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create();
        $this->setUser($user);

        $this->createJobConfiguration($user);

        $request = new Request([], [
            'job-configuration' => 'job-configuration-label',
            'schedule' => '* * * * *',
            'schedule-modifier' => null,
        ]);

        $this->callCreateAction($request, $user);
        $response = $this->callCreateAction($request, $user);

        $this->assertTrue($response->isClientError());
        $this->assertEquals(
            '{"code":2,"message":"Matching scheduled job exists"}',
            $response->headers->get('x-scheduledjobcreate-error')
        );
    }

    public function testCreateActionSuccess()
    {
        $scheduledJobRepository = self::$container->get(ScheduledJobRepository::class);

        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create();
        $this->setUser($user);

        $this->createJobConfiguration($user);

        $request = new Request([], [
            'job-configuration' => 'job-configuration-label',
            'schedule' => '* * * * *',
            'schedule-modifier' => '[ `date +\%d` -le 7 ]',
        ]);

        $response = $this->callCreateAction($request, $user);

        /* @var ScheduledJob $scheduledJob */
        $scheduledJob = $scheduledJobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('http://localhost/scheduledjob/' . $scheduledJob->getId() . '/'));
    }

    /**
     * @param User $user
     */
    private function createJobConfiguration(User $user)
    {
        $jobConfigurationCreateController = new JobConfigurationController(
            self::$container->get('router'),
            MockFactory::createApplicationStateService(),
            self::$container->get(ConfigurationService::class)
        );

        $request = new Request([], [
            'label' => 'job-configuration-label',
            'website' => 'website value',
            'type' => 'type value',
            'task-configuration' => [
                'HTML Validation' => [],
            ],
        ]);

        $jobConfigurationCreateController->createAction(
            self::$container->get(UserService::class),
            self::$container->get(WebSiteService::class),
            self::$container->get(TaskTypeService::class),
            self::$container->get(JobTypeService::class),
            $user,
            $request
        );
    }

    /**
     * @param Request $request
     * @param User $user
     *
     * @return RedirectResponse|Response
     */
    private function callCreateAction(Request $request, User $user)
    {
        return $this->scheduledJobController->createAction(
            self::$container->get(UserService::class),
            self::$container->get(ConfigurationService::class),
            self::$container->get(CronModifierValidationService::class),
            $user,
            $request
        );
    }
}
