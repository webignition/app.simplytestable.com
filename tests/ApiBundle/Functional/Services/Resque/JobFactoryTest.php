<?php

namespace Tests\ApiBundle\Functional\Services\Resque;

use SimplyTestable\ApiBundle\Resque\Job\Job\PrepareJob;
use SimplyTestable\ApiBundle\Resque\Job\Job\ResolveJob;
use SimplyTestable\ApiBundle\Resque\Job\ScheduledJob\ExecuteJob;
use SimplyTestable\ApiBundle\Resque\Job\Stripe\ProcessEventJob;
use SimplyTestable\ApiBundle\Resque\Job\Task\AssignCollectionJob;
use SimplyTestable\ApiBundle\Resque\Job\Task\CancelCollectionJob;
use SimplyTestable\ApiBundle\Resque\Job\Worker\ActivateVerifyJob;
use SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks\NotifyJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskPreProcessor\Factory;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\Worker\TaskNotificationService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;
use SimplyTestable\ApiBundle\Services\WorkerTaskCancellationService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class JobFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
    }

    public function testCreateWithInvalidQueue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Queue "foo" is not valid');
        $this->expectExceptionCode(JobFactory::EXCEPTION_CODE_INVALID_QUEUE);

        $this->jobFactory->create('foo');
    }

    /**
     * @dataProvider createWithMissingRequiredArgsDataProvider
     *
     * @param string $queue
     * @param array $args
     * @param string $expectedExceptionMessage
     */
    public function testCreateWithMissingRequiredArgs($queue, $args, $expectedExceptionMessage)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectExceptionCode(JobFactory::EXCEPTION_CODE_MISSING_REQUIRED_ARG);

        $this->jobFactory->create($queue, $args);
    }

    /**
     * @return array
     */
    public function createWithMissingRequiredArgsDataProvider()
    {
        return [
            'job-prepare' => [
                'queue' => 'job-prepare',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "id" is missing',
            ],
            'job-resolve' => [
                'queue' => 'job-resolve',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "id" is missing',
            ],
            'task-assign-collection' => [
                'queue' => 'task-assign-collection',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "ids" is missing',
            ],
            'task-cancel-collection' => [
                'queue' => 'task-cancel-collection',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "ids" is missing',
            ],
            'worker-activate-verify' => [
                'queue' => 'worker-activate-verify',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "id" is missing',
            ],
            'stripe-event' => [
                'queue' => 'stripe-event',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "stripeId" is missing',
            ],
            'scheduledjob-execute' => [
                'queue' => 'scheduledjob-execute',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedExceptionMessage' => 'Required argument "id" is missing',
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $queue
     * @param array $args
     * @param string $expectedJobClass
     * @param string $expectedQueue
     * @param array $expectedArgs
     */
    public function testCreate($queue, $args, $expectedJobClass, $expectedQueue, $expectedArgs)
    {
        $job = $this->jobFactory->create($queue, $args);

        $this->assertInstanceOf($expectedJobClass, $job);
        $this->assertEquals($job->queue, $expectedQueue);
        $this->assertEquals($job->args, $expectedArgs);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'job-prepare' => [
                'queue' => 'job-prepare',
                'args' => [
                    'id' => 1,
                ],
                'expectedJobClass' => PrepareJob::class,
                'expectedQueue' => 'job-prepare',
                'expectedArgs' => [
                    'id' => 1,
                    'serviceIds' => [
                        ApplicationStateService::class,
                        QueueService::class,
                        'simplytestable.services.resque.jobfactory',
                        JobPreparationService::class,
                        CrawlJobContainerService::class,
                        'logger',
                        'doctrine.orm.entity_manager',
                    ],
                    'parameters' => [
                        'predefinedDomainsToIgnore' => [
                            'css-validation' => [
                                'cdnjs.cloudflare.com',
                                'ajax.googleapis.com',
                                'netdna.bootstrapcdn.com',
                                'ajax.aspnetcdn.com',
                                'static.nrelate.com',
                            ],
                            'js-static-analysis' => [
                                'cdnjs.cloudflare.com',
                                'ajax.googleapis.com',
                                'netdna.bootstrapcdn.com',
                                'ajax.aspnetcdn.com',
                                'static.nrelate.com',
                                'connect.facebook.net',
                            ],
                        ],
                    ],
                ],
            ],
            'job-resolve' => [
                'queue' => 'job-resolve',
                'args' => [
                    'id' => 1,
                ],
                'expectedJobClass' => ResolveJob::class,
                'expectedQueue' => 'job-resolve',
                'expectedArgs' => [
                    'id' => 1,
                    'serviceIds' => [
                        ApplicationStateService::class,
                        QueueService::class,
                        'simplytestable.services.resque.jobfactory',
                        WebsiteResolutionService::class,
                        JobPreparationService::class,
                        'doctrine.orm.entity_manager',
                    ],
                    'parameters' => [
                        'predefinedDomainsToIgnore' => [
                            'css-validation' => [
                                'cdnjs.cloudflare.com',
                                'ajax.googleapis.com',
                                'netdna.bootstrapcdn.com',
                                'ajax.aspnetcdn.com',
                                'static.nrelate.com',
                            ],
                            'js-static-analysis' => [
                                'cdnjs.cloudflare.com',
                                'ajax.googleapis.com',
                                'netdna.bootstrapcdn.com',
                                'ajax.aspnetcdn.com',
                                'static.nrelate.com',
                                'connect.facebook.net',
                            ],
                        ],
                    ],
                ],
            ],
            'task-assign-collection' => [
                'queue' => 'task-assign-collection',
                'args' => [
                    'ids' => '1,2,3',
                ],
                'expectedJobClass' => AssignCollectionJob::class,
                'expectedQueue' => 'task-assign-collection',
                'expectedArgs' => [
                    'ids' => '1,2,3',
                    'serviceIds' => [
                        ApplicationStateService::class,
                        'doctrine.orm.entity_manager',
                        Factory::class,
                        QueueService::class,
                        'simplytestable.services.resque.jobfactory',
                        StateService::class,
                        WorkerTaskAssignmentService::class,
                        'logger',
                    ],
                ],
            ],
            'task-cancel-collection' => [
                'queue' => 'task-cancel-collection',
                'args' => [
                    'ids' => '1,2,3',
                ],
                'expectedJobClass' => CancelCollectionJob::class,
                'expectedQueue' => 'task-cancel-collection',
                'expectedArgs' => [
                    'ids' => '1,2,3',
                    'serviceIds' => [
                        ApplicationStateService::class,
                        TaskService::class,
                        WorkerTaskCancellationService::class,
                        'logger',
                        'doctrine.orm.entity_manager',
                    ],
                ],
            ],
            'worker-activate-verify' => [
                'queue' => 'worker-activate-verify',
                'args' => [
                    'id' => 1,
                ],
                'expectedJobClass' => ActivateVerifyJob::class,
                'expectedQueue' => 'worker-activate-verify',
                'expectedArgs' => [
                    'id' => 1,
                    'serviceIds' => [
                        ApplicationStateService::class,
                        WorkerActivationRequestService::class,
                        'doctrine.orm.entity_manager',
                    ],
                ],
            ],
            'stripe-event' => [
                'queue' => 'stripe-event',
                'args' => [
                    'stripeId' => 'evt_2c6KUnrLeIFqQv',
                ],
                'expectedJobClass' => ProcessEventJob::class,
                'expectedQueue' => 'stripe-event',
                'expectedArgs' => [
                    'stripeId' => 'evt_2c6KUnrLeIFqQv',
                    'serviceIds' => [
                        ApplicationStateService::class,
                        'doctrine.orm.entity_manager',
                        'logger',
                        'event_dispatcher',
                    ],
                ],
            ],
            'tasks-notify' => [
                'queue' => 'tasks-notify',
                'args' => [],
                'expectedJobClass' => NotifyJob::class,
                'expectedQueue' => 'tasks-notify',
                'expectedArgs' => [
                    'serviceIds' => [
                        TaskNotificationService::class,
                    ],
                ],
            ],
            'scheduledjob-execute' => [
                'queue' => 'scheduledjob-execute',
                'args' => [
                    'id' => 1,
                ],
                'expectedJobClass' => ExecuteJob::class,
                'expectedQueue' => 'scheduledjob-execute',
                'expectedArgs' => [
                    'id' => 1,
                    'serviceIds' => [
                        ApplicationStateService::class,
                        QueueService::class,
                        'simplytestable.services.resque.jobfactory',
                        'doctrine.orm.entity_manager',
                        'simplytestable.services.job.startservice',
                        JobService::class,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getJobClassNameDataProvider
     *
     * @param string $queue
     * @param string $expectedJobClassName
     */
    public function testGetJobClassName($queue, $expectedJobClassName)
    {
        $jobClassName = $this->jobFactory->getJobClassName($queue);

        $this->assertEquals($expectedJobClassName, $jobClassName);
    }

    /**
     * @return array
     */
    public function getJobClassNameDataProvider()
    {
        return [
            'job-prepare' => [
                'queue' => 'job-prepare',
                'expectedJobClassName' => 'SimplyTestable\ApiBundle\Resque\Job\Job\PrepareJob',
            ],
            'job-resolve' => [
                'queue' => 'job-resolve',
                'expectedJobClassName' => 'SimplyTestable\ApiBundle\Resque\Job\Job\ResolveJob',
            ],
            'task-assign-collection' => [
                'queue' => 'task-assign-collection',
                'expectedJobClassName' => 'SimplyTestable\ApiBundle\Resque\Job\Task\AssignCollectionJob',
            ],
            'task-cancel-collection' => [
                'queue' => 'task-cancel-collection',
                'expectedJobClassName' => 'SimplyTestable\ApiBundle\Resque\Job\Task\CancelCollectionJob',
            ],
            'worker-activate-verify' => [
                'queue' => 'worker-activate-verify',
                'expectedJobClassName' => 'SimplyTestable\ApiBundle\Resque\Job\Worker\ActivateVerifyJob',
            ],
            'stripe-event' => [
                'queue' => 'stripe-event',
                'expectedJobClassName' => 'SimplyTestable\ApiBundle\Resque\Job\Stripe\ProcessEventJob',
            ],
            'tasks-notify' => [
                'queue' => 'tasks-notify',
                'expectedJobClassName' => 'SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks\NotifyJob',
            ],
            'scheduledjob-execute' => [
                'queue' => 'scheduledjob-execute',
                'expectedJobClassName' => 'SimplyTestable\ApiBundle\Resque\Job\ScheduledJob\ExecuteJob',
            ],
            'foo' => [
                'queue' => 'foo',
                'expectedJobClassName' => null,
            ],
        ];
    }
}
