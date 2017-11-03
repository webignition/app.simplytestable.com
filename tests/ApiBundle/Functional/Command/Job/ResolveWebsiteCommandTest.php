<?php

namespace Tests\ApiBundle\Functional\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\CurlExceptionFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ResolveWebsiteCommandTest extends AbstractBaseTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var ResolveWebsiteCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);

        $this->command = $this->container->get('simplytestable.command.job.resolvewebsite');
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $returnCode = $this->command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);

        $maintenanceController->disableReadOnlyAction();
    }

    /**
     * @dataProvider runWithJobInWrongStateDataProvider
     *
     * @param string $stateName
     */
    public function testRunWithJobInWrongState($stateName)
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_STATE => $stateName,
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE, $returnCode);
    }

    /**
     * @return array
     */
    public function runWithJobInWrongStateDataProvider()
    {
        return [
            JobService::CANCELLED_STATE => [
                'stateName' => JobService::CANCELLED_STATE,
            ],
            JobService::COMPLETED_STATE => [
                'stateName' => JobService::COMPLETED_STATE,
            ],
            JobService::IN_PROGRESS_STATE => [
                'stateName' => JobService::IN_PROGRESS_STATE,
            ],
            JobService::PREPARING_STATE => [
                'stateName' => JobService::PREPARING_STATE,
            ],
            JobService::QUEUED_STATE => [
                'stateName' => JobService::QUEUED_STATE,
            ],
            JobService::FAILED_NO_SITEMAP_STATE => [
                'stateName' => JobService::FAILED_NO_SITEMAP_STATE,
            ],
            JobService::REJECTED_STATE => [
                'stateName' => JobService::REJECTED_STATE,
            ],
            JobService::RESOLVING_STATE => [
                'stateName' => JobService::RESOLVING_STATE,
            ],
            JobService::RESOLVED_STATE => [
                'stateName' => JobService::RESOLVED_STATE,
            ],
        ];
    }

    public function testRunIsRejected()
    {
        $job = $this->jobFactory->create();

        $this->queueHttpFixtures([
            CurlExceptionFactory::create('operation timed out', 28),
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_OK, $returnCode);
        $this->assertEquals(JobService::REJECTED_STATE, $job->getState()->getName());
    }

    /**
     * @dataProvider runForSingleUrlJobDataProvider
     *
     * @param array $jobValues
     * @param array $expectedTaskParameters
     */
    public function testRunForSingleUrlJob($jobValues, $expectedTaskParameters)
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $jobValues[JobFactory::KEY_TYPE] = JobTypeService::SINGLE_URL_NAME;

        $job = $this->jobFactory->create($jobValues);

        $this->queueHttpFixtures([
            HttpFixtureFactory::createStandardResolveResponse(),
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_OK, $returnCode);
        $this->assertEquals(JobService::QUEUED_STATE, $job->getState()->getName());

        /* @var Task[] $tasks */
        $tasks = $job->getTasks()->toArray();

        foreach ($tasks as $taskIndex => $task) {
            $this->assertEquals($expectedTaskParameters[$taskIndex], $task->getParametersArray());
        }

        $this->assertTrue($resqueQueueService->contains(
            'task-assign-collection',
            ['ids' => implode(',', $job->getTaskIds())]
        ));
    }

    /**
     * @return array
     */
    public function runForSingleUrlJobDataProvider()
    {
        return [
            'html validation only' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'expectedTaskParameters' => [
                    [],
                ],
            ],
            'css validation only, no domains to ignore, do not ignore common cdns' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                ],
                'expectedTaskParameters' => [
                    [],
                ],
            ],
            'css validation only, no domains to ignore, ignore common cdns' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::CSS_VALIDATION_TYPE,
                    ],
                    JobFactory::KEY_TEST_TYPE_OPTIONS => [
                        TaskTypeService::CSS_VALIDATION_TYPE => [
                            'domains-to-ignore' => [],
                            'ignore-common-cdns' => true,
                        ],
                    ],
                ],
                'expectedTaskParameters' => [
                    [
                        'domains-to-ignore' => [
                            'cdnjs.cloudflare.com',
                            'ajax.googleapis.com',
                            'netdna.bootstrapcdn.com',
                            'ajax.aspnetcdn.com',
                            'static.nrelate.com',
                        ],
                        'ignore-common-cdns' =>  true,
                    ],
                ],
            ],
        ];
    }

    public function testRunForFullSiteJob()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $job = $this->jobFactory->create();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createStandardResolveResponse(),
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());


        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_OK, $returnCode);
        $this->assertEquals(JobService::RESOLVED_STATE, $job->getState()->getName());

        $this->assertTrue($resqueQueueService->contains(
            'job-prepare',
            ['id' => $job->getId()]
        ));
    }
}
