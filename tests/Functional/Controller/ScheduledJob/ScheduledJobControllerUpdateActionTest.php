<?php

namespace App\Tests\Functional\Controller\ScheduledJob;

use App\Entity\ScheduledJob;
use App\Entity\User;
use App\Services\Job\ConfigurationService;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Tests\Services\JobConfigurationFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Services\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use App\Services\ScheduledJob\CronModifier\ValidationService as CronModifierValidationService;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerUpdateActionTest extends AbstractScheduledJobControllerTest
{
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

        $jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);

        $userFactory = self::$container->get(UserFactory::class);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $scheduledJobService = self::$container->get(ScheduledJobService::class);
        $this->scheduledJob = $scheduledJobService->create(
            $jobConfiguration,
            '* * * * *',
            'foo'
        );
    }

    public function testUpdateActionGetRequest()
    {
        $router = self::$container->get('router');
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
        $router = self::$container->get('router');
        $requestUrl = $router->generate('scheduledjob_update', [
            'id' => $this->scheduledJob->getId(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isRedirect('http://localhost/scheduledjob/' . $this->scheduledJob->getId() . '/'));
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
            $userFactory = self::$container->get(UserFactory::class);

            foreach ($jobConfigurationValuesCollection as $jobConfigurationValues) {
                $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $userFactory->create([
                    UserFactory::KEY_EMAIL => $jobConfigurationValues[JobConfigurationFactory::KEY_USER]
                ]);

                $jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);
                $jobConfigurationFactory->create($jobConfigurationValues);
            }
        }

        if (!empty($scheduledJobValuesCollection)) {
            $scheduledJobService = self::$container->get(ScheduledJobService::class);
            $jobConfigurationService = self::$container->get(ConfigurationService::class);

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

        $response = $this->callUpdateAction($postData);

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
            $userFactory = self::$container->get(UserFactory::class);

            foreach ($jobConfigurationValuesCollection as $jobConfigurationValues) {
                $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $userFactory->create([
                    UserFactory::KEY_EMAIL => $jobConfigurationValues[JobConfigurationFactory::KEY_USER]
                ]);

                $jobConfigurationFactory = self::$container->get(JobConfigurationFactory::class);
                $jobConfigurationFactory->create($jobConfigurationValues);
            }
        }

        $response = $this->callUpdateAction($postData);

        $this->assertTrue($response->isRedirect('http://localhost/scheduledjob/' . $this->scheduledJob->getId() . '/'));

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

    /**
     * @param array $postData
     *
     * @return RedirectResponse|Response
     */
    private function callUpdateAction($postData)
    {
        return $this->scheduledJobController->updateAction(
            self::$container->get(ConfigurationService::class),
            self::$container->get(CronModifierValidationService::class),
            new Request([], $postData),
            $this->scheduledJob->getId()
        );
    }
}
