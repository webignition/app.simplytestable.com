<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\UpdateController;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;

class ScheduledJobUpdateControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UpdateController
     */
    private $scheduledJobUpdateController;

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

        $this->scheduledJobUpdateController = new UpdateController();
        $this->scheduledJobUpdateController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->create();

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

    public function testGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_update_update', [
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

    public function testPostRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_update_update', [
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
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $response = $this->scheduledJobUpdateController->updateAction(new Request(), 0);
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY);

        $response = $this->scheduledJobUpdateController->updateAction(new Request(), 0);
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    public function testUpdateActionScheduledJobNotFound()
    {
        $response = $this->scheduledJobUpdateController->updateAction(new Request(), 0);

        $this->assertTrue($response->isNotFound());
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
            $jobConfigurationService = $this->container->get('simplytestable.services.job.configurationservice');

            foreach ($scheduledJobValuesCollection as $scheduledJobValues) {
                $jobConfigurationService->setUser($this->user);
                $jobConfiguration = $jobConfigurationService->get($scheduledJobValues['job-configuration']);

                $scheduledJobService->create(
                    $jobConfiguration,
                    $scheduledJobValues['schedule'],
                    $scheduledJobValues['cron-modifier'],
                    $scheduledJobValues['is-recurring']
                );
            }
        }

        $response = $this->scheduledJobUpdateController->updateAction(
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

        $response = $this->scheduledJobUpdateController->updateAction(
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
