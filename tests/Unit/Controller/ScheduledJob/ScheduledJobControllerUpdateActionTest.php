<?php

namespace App\Tests\Unit\Controller\ScheduledJob;

use App\Entity\ScheduledJob;
use App\Entity\User;
use App\Services\ApplicationStateService;
use App\Services\Job\ConfigurationService;
use App\Services\ScheduledJob\Service as ScheduledJobService;
use App\Tests\Factory\JobConfigurationFactory;
use App\Tests\Factory\MockFactory;
use App\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * @group Controller/ScheduledJob
 */
class ScheduledJobControllerUpdateActionTest extends AbstractScheduledJobControllerTest
{
    const SCHEDULED_JOB_ID = 1;

    public function testUpdateActionInMaintenanceReadOnlyMode()
    {
        $scheduledJobController = $this->createScheduledJobController([
            ApplicationStateService::class => MockFactory::createApplicationStateService(true),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $scheduledJobController->updateAction(
            MockFactory::createJobConfigurationService(),
            MockFactory::createCronModifierValidationService(),
            new Request(),
            self::SCHEDULED_JOB_ID
        );
    }

    public function testUpdateActionScheduledJobNotFound()
    {
        $scheduledJobController = $this->createScheduledJobController([
            ScheduledJobService::class => MockFactory::createScheduledJobService([
                'get' => [
                    'with' => self::SCHEDULED_JOB_ID,
                    'return' => null,
                ],
            ])
        ]);

        $this->expectException(NotFoundHttpException::class);

        $scheduledJobController->updateAction(
            MockFactory::createJobConfigurationService(),
            MockFactory::createCronModifierValidationService(),
            new Request(),
            self::SCHEDULED_JOB_ID
        );
    }
//
//    /**
//     * @dataProvider updateActionClientErrorDataProvider
//     *
//     * @param array $jobConfigurationValuesCollection
//     * @param array $scheduledJobValuesCollection
//     * @param array $postData
//     * @param array $expectedResponseErrorHeader
//     */
//    public function testUpdateActionClientError(
//        $jobConfigurationValuesCollection,
//        $scheduledJobValuesCollection,
//        $postData,
//        $expectedResponseErrorHeader
//    ) {
//        if (!empty($jobConfigurationValuesCollection)) {
//            $userFactory = new UserFactory($this->container);
//
//            foreach ($jobConfigurationValuesCollection as $jobConfigurationValues) {
//                $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $userFactory->create([
//                    UserFactory::KEY_EMAIL => $jobConfigurationValues[JobConfigurationFactory::KEY_USER]
//                ]);
//
//                $jobConfigurationFactory = new JobConfigurationFactory($this->container);
//                $jobConfigurationFactory->create($jobConfigurationValues);
//            }
//        }
//
//        if (!empty($scheduledJobValuesCollection)) {
//            $scheduledJobService = $this->container->get(ScheduledJobService::class);
//            $jobConfigurationService = $this->container->get(ConfigurationService::class);
//
//            foreach ($scheduledJobValuesCollection as $scheduledJobValues) {
//                $jobConfiguration = $jobConfigurationService->get($scheduledJobValues['job-configuration']);
//
//                $scheduledJobService->create(
//                    $jobConfiguration,
//                    $scheduledJobValues['schedule'],
//                    $scheduledJobValues['cron-modifier'],
//                    $scheduledJobValues['is-recurring']
//                );
//            }
//        }
//
//        $response = $this->scheduledJobController->updateAction(
//            new Request([], $postData),
//            $this->scheduledJob->getId()
//        );
//
//        $this->assertTrue($response->isClientError());
//
//        $this->assertEquals(
//            $expectedResponseErrorHeader,
//            json_decode($response->headers->get('X-ScheduledJobUpdate-Error'), true)
//        );
//    }
//
//    /**
//     * @return array
//     */
//    public function updateActionClientErrorDataProvider()
//    {
//        return [
//            'unknown job configuration' => [
//                'jobConfigurationValuesCollection' => [],
//                'scheduledJobValuesCollection' => [],
//                'postData' => [
//                    'job-configuration' => 'foo',
//                ],
//                'expectedResponseErrorHeader' => [
//                    'code' => 99,
//                    'message' => 'Unknown job configuration',
//                ],
//            ],
//            'invalid schedule' => [
//                'jobConfigurationValuesCollection' => [],
//                'scheduledJobValuesCollection' => [],
//                'postData' => [
//                    'schedule' => 'foo',
//                ],
//                'expectedResponseErrorHeader' => [
//                    'code' => 98,
//                    'message' => 'Invalid schedule',
//                ],
//            ],
//            'invalid schedule modifier' => [
//                'jobConfigurationValuesCollection' => [],
//                'scheduledJobValuesCollection' => [],
//                'postData' => [
//                    'schedule-modifier' => 'foo',
//                ],
//                'expectedResponseErrorHeader' => [
//                    'code' => 97,
//                    'message' => 'Invalid schedule modifier',
//                ],
//            ],
//            'matching scheduled job; null cron modifier' => [
//                'jobConfigurationValuesCollection' => [
//                    [
//                        JobConfigurationFactory::KEY_USER => 'user@example.com',
//                        JobConfigurationFactory::KEY_LABEL => 'new-job-configuration',
//                    ],
//                ],
//                'scheduledJobValuesCollection' => [
//                    [
//                        'job-configuration' => 'new-job-configuration',
//                        'schedule' => '* * * * *',
//                        'cron-modifier' => null,
//                        'is-recurring' => true,
//                    ],
//                ],
//                'postData' => [
//                    'job-configuration' => 'new-job-configuration',
//                ],
//                'expectedResponseErrorHeader' => [
//                    'code' => 2,
//                    'message' => 'Matching scheduled job exists',
//                ],
//            ],
//            'matching scheduled job; non-null cron modifier' => [
//                'jobConfigurationValuesCollection' => [
//                    [
//                        JobConfigurationFactory::KEY_USER => 'user@example.com',
//                        JobConfigurationFactory::KEY_LABEL => 'new-job-configuration',
//                    ],
//                ],
//                'scheduledJobValuesCollection' => [
//                    [
//                        'job-configuration' => 'new-job-configuration',
//                        'schedule' => '* * * * *',
//                        'cron-modifier' => 'foo',
//                        'is-recurring' => true,
//                    ],
//                ],
//                'postData' => [
//                    'job-configuration' => 'new-job-configuration',
//                ],
//                'expectedResponseErrorHeader' => [
//                    'code' => 2,
//                    'message' => 'Matching scheduled job exists',
//                ],
//            ],
//        ];
//    }
//
//    /**
//     * @dataProvider updateActionSuccessDataProvider
//     *
//     * @param array $jobConfigurationValuesCollection
//     * @param array $postData
//     * @param array $expectedScheduledJobValues
//     */
//    public function testUpdateActionSuccess(
//        $jobConfigurationValuesCollection,
//        $postData,
//        $expectedScheduledJobValues
//    ) {
//        if (!empty($jobConfigurationValuesCollection)) {
//            $userFactory = new UserFactory($this->container);
//
//            foreach ($jobConfigurationValuesCollection as $jobConfigurationValues) {
//                $jobConfigurationValues[JobConfigurationFactory::KEY_USER] = $userFactory->create([
//                    UserFactory::KEY_EMAIL => $jobConfigurationValues[JobConfigurationFactory::KEY_USER]
//                ]);
//
//                $jobConfigurationFactory = new JobConfigurationFactory($this->container);
//                $jobConfigurationFactory->create($jobConfigurationValues);
//            }
//        }
//
//        $response = $this->scheduledJobController->updateAction(
//            new Request([], $postData),
//            $this->scheduledJob->getId()
//        );
//
//        $this->assertTrue($response->isRedirect('/scheduledjob/' . $this->scheduledJob->getId() . '/'));
//
//        $this->assertEquals(
//            $expectedScheduledJobValues['job-configuration'],
//            $this->scheduledJob->getJobConfiguration()->getLabel()
//        );
//
//        $this->assertEquals(
//            $expectedScheduledJobValues['schedule'],
//            $this->scheduledJob->getCronJob()->getSchedule()
//        );
//
//        $this->assertEquals(
//            $expectedScheduledJobValues['cron-modifier'],
//            $this->scheduledJob->getCronModifier()
//        );
//
//        $this->assertEquals(
//            $expectedScheduledJobValues['is-recurring'],
//            $this->scheduledJob->getIsRecurring()
//        );
//    }
//
//    /**
//     * @return array
//     */
//    public function updateActionSuccessDataProvider()
//    {
//        return [
//            'no post data' => [
//                'jobConfigurationValuesCollection' => [],
//                'postData' => [],
//                'expectedScheduledJobValues' => [
//                    'job-configuration' => 'label',
//                    'schedule' => '* * * * *',
//                    'cron-modifier' => 'foo',
//                    'is-recurring' => true,
//                ],
//            ],
//            'post data same as current data' => [
//                'jobConfigurationValuesCollection' => [],
//                'postData' => [
//                    'job-configuration' => 'label',
//                    'schedule' => '* * * * *',
//                    'is-recurring' => true,
//                ],
//                'expectedScheduledJobValues' => [
//                    'job-configuration' => 'label',
//                    'schedule' => '* * * * *',
//                    'cron-modifier' => 'foo',
//                    'is-recurring' => true,
//                ],
//            ],
//            'with changes' => [
//                'jobConfigurationValuesCollection' => [
//                    [
//                        JobConfigurationFactory::KEY_USER => 'user@example.com',
//                        JobConfigurationFactory::KEY_LABEL => 'new-label',
//                    ],
//                ],
//                'postData' => [
//                    'job-configuration' => 'new-label',
//                    'schedule' => '* * * * 1',
//                    'schedule-modifier' => '[ `date +\%d` -le 7 ]',
//                    'is-recurring' => false,
//                ],
//                'expectedScheduledJobValues' => [
//                    'job-configuration' => 'new-label',
//                    'schedule' => '* * * * 1',
//                    'cron-modifier' => '[ `date +\%d` -le 7 ]',
//                    'is-recurring' => false,
//                ],
//            ],
//        ];
//    }
}
