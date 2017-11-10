<?php

namespace Tests\ApiBundle\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJobController;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService;
use Tests\ApiBundle\Factory\JobConfigurationFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ScheduledJobControllerUpdateActionTest extends AbstractBaseTestCase
{
    /**
     * @var ScheduledJobController
     */
    private $scheduledJobController;

    /**
     * @var ScheduledJob
     */
    private $scheduledJob;

    /**
     * @var User
     */
    private $user;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->scheduledJobController = new ScheduledJobController();
        $this->scheduledJobController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $this->scheduledJob = $scheduledJobService->create(
            $jobConfiguration,
            '* * * * *',
            'foo'
        );
    }

    public function testUpdateActionGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_update', [
            'id' => 0,
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testUpdateActionPostRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_update', [
            'id' => $this->scheduledJob->getId(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isRedirect('/scheduledjob/' . $this->scheduledJob->getId() . '/'));
    }

    public function testUpdateActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->scheduledJobController->updateAction(new Request(), 0);
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    public function testUpdateActionScheduledJobNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->scheduledJobController->updateAction(new Request(), 0);
    }

    /**
     * @dataProvider updateActionClientErrorDataProvider
     *
     * @param array $jobConfigurationValuesCollection
     * @param array $scheduledJobValuesCollection
     * @param array $postData
     * @param array $expectedResponseErrorHeader
     */
    public function testUpdateActionClientError(
        $jobConfigurationValuesCollection,
        $scheduledJobValuesCollection,
        $postData,
        $expectedResponseErrorHeader
    ) {
        if (!empty($jobConfigurationValuesCollection)) {
            $userFactory = new UserFactory($this->container);

            foreach ($jobConfigurationValuesCollection as $jobConfigurationValues) {
                $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $userFactory->create([
                    UserFactory::KEY_EMAIL => $jobConfigurationValues[JobConfigurationFactory::KEY_USER]
                ]);

                $jobConfigurationFactory = new JobConfigurationFactory($this->container);
                $jobConfigurationFactory->create($jobConfigurationValues);
            }
        }

        if (!empty($scheduledJobValuesCollection)) {
            $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
            $jobConfigurationService = $this->container->get(ConfigurationService::class);

            foreach ($scheduledJobValuesCollection as $scheduledJobValues) {
                $jobConfiguration = $jobConfigurationService->get($scheduledJobValues['job-configuration']);

                $scheduledJobService->create(
                    $jobConfiguration,
                    $scheduledJobValues['schedule'],
                    $scheduledJobValues['cron-modifier'],
                    $scheduledJobValues['is-recurring']
                );
            }
        }

        $response = $this->scheduledJobController->updateAction(
            new Request([], $postData),
            $this->scheduledJob->getId()
        );

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            $expectedResponseErrorHeader,
            json_decode($response->headers->get('X-ScheduledJobUpdate-Error'), true)
        );
    }

    /**
     * @return array
     */
    public function updateActionClientErrorDataProvider()
    {
        return [
            'unknown job configuration' => [
                'jobConfigurationValuesCollection' => [],
                'scheduledJobValuesCollection' => [],
                'postData' => [
                    'job-configuration' => 'foo',
                ],
                'expectedResponseErrorHeader' => [
                    'code' => 99,
                    'message' => 'Unknown job configuration',
                ],
            ],
            'invalid schedule' => [
                'jobConfigurationValuesCollection' => [],
                'scheduledJobValuesCollection' => [],
                'postData' => [
                    'schedule' => 'foo',
                ],
                'expectedResponseErrorHeader' => [
                    'code' => 98,
                    'message' => 'Invalid schedule',
                ],
            ],
            'invalid schedule modifier' => [
                'jobConfigurationValuesCollection' => [],
                'scheduledJobValuesCollection' => [],
                'postData' => [
                    'schedule-modifier' => 'foo',
                ],
                'expectedResponseErrorHeader' => [
                    'code' => 97,
                    'message' => 'Invalid schedule modifier',
                ],
            ],
            'matching scheduled job; null cron modifier' => [
                'jobConfigurationValuesCollection' => [
                    [
                        JobConfigurationFactory::KEY_USER => 'user@example.com',
                        JobConfigurationFactory::KEY_LABEL => 'new-job-configuration',
                    ],
                ],
                'scheduledJobValuesCollection' => [
                    [
                        'job-configuration' => 'new-job-configuration',
                        'schedule' => '* * * * *',
                        'cron-modifier' => null,
                        'is-recurring' => true,
                    ],
                ],
                'postData' => [
                    'job-configuration' => 'new-job-configuration',
                ],
                'expectedResponseErrorHeader' => [
                    'code' => 2,
                    'message' => 'Matching scheduled job exists',
                ],
            ],
            'matching scheduled job; non-null cron modifier' => [
                'jobConfigurationValuesCollection' => [
                    [
                        JobConfigurationFactory::KEY_USER => 'user@example.com',
                        JobConfigurationFactory::KEY_LABEL => 'new-job-configuration',
                    ],
                ],
                'scheduledJobValuesCollection' => [
                    [
                        'job-configuration' => 'new-job-configuration',
                        'schedule' => '* * * * *',
                        'cron-modifier' => 'foo',
                        'is-recurring' => true,
                    ],
                ],
                'postData' => [
                    'job-configuration' => 'new-job-configuration',
                ],
                'expectedResponseErrorHeader' => [
                    'code' => 2,
                    'message' => 'Matching scheduled job exists',
                ],
            ],
        ];
    }

    /**
     * @dataProvider updateActionSuccessDataProvider
     *
     * @param array $jobConfigurationValuesCollection
     * @param array $postData
     * @param array $expectedScheduledJobValues
     */
    public function testUpdateActionSuccess(
        $jobConfigurationValuesCollection,
        $postData,
        $expectedScheduledJobValues
    ) {
        if (!empty($jobConfigurationValuesCollection)) {
            $userFactory = new UserFactory($this->container);

            foreach ($jobConfigurationValuesCollection as $jobConfigurationValues) {
                $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $userFactory->create([
                    UserFactory::KEY_EMAIL => $jobConfigurationValues[JobConfigurationFactory::KEY_USER]
                ]);

                $jobConfigurationFactory = new JobConfigurationFactory($this->container);
                $jobConfigurationFactory->create($jobConfigurationValues);
            }
        }

        $response = $this->scheduledJobController->updateAction(
            new Request([], $postData),
            $this->scheduledJob->getId()
        );

        $this->assertTrue($response->isRedirect('/scheduledjob/' . $this->scheduledJob->getId() . '/'));

        $this->assertEquals(
            $expectedScheduledJobValues['job-configuration'],
            $this->scheduledJob->getJobConfiguration()->getLabel()
        );

        $this->assertEquals(
            $expectedScheduledJobValues['schedule'],
            $this->scheduledJob->getCronJob()->getSchedule()
        );

        $this->assertEquals(
            $expectedScheduledJobValues['cron-modifier'],
            $this->scheduledJob->getCronModifier()
        );

        $this->assertEquals(
            $expectedScheduledJobValues['is-recurring'],
            $this->scheduledJob->getIsRecurring()
        );
    }

    /**
     * @return array
     */
    public function updateActionSuccessDataProvider()
    {
        return [
            'no post data' => [
                'jobConfigurationValuesCollection' => [],
                'postData' => [],
                'expectedScheduledJobValues' => [
                    'job-configuration' => 'label',
                    'schedule' => '* * * * *',
                    'cron-modifier' => 'foo',
                    'is-recurring' => true,
                ],
            ],
            'post data same as current data' => [
                'jobConfigurationValuesCollection' => [],
                'postData' => [
                    'job-configuration' => 'label',
                    'schedule' => '* * * * *',
                    'is-recurring' => true,
                ],
                'expectedScheduledJobValues' => [
                    'job-configuration' => 'label',
                    'schedule' => '* * * * *',
                    'cron-modifier' => 'foo',
                    'is-recurring' => true,
                ],
            ],
            'with changes' => [
                'jobConfigurationValuesCollection' => [
                    [
                        JobConfigurationFactory::KEY_USER => 'user@example.com',
                        JobConfigurationFactory::KEY_LABEL => 'new-label',
                    ],
                ],
                'postData' => [
                    'job-configuration' => 'new-label',
                    'schedule' => '* * * * 1',
                    'schedule-modifier' => '[ `date +\%d` -le 7 ]',
                    'is-recurring' => false,
                ],
                'expectedScheduledJobValues' => [
                    'job-configuration' => 'new-label',
                    'schedule' => '* * * * 1',
                    'cron-modifier' => '[ `date +\%d` -le 7 ]',
                    'is-recurring' => false,
                ],
            ],
        ];
    }
}
