<?php

namespace App\Tests\Functional\Command\Job;

use App\Tests\Services\JobFactory;
use GuzzleHttp\Psr7\Response;
use App\Command\Job\ResolveWebsiteCommand;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Services\HttpClientService;
use App\Services\JobTypeService;
use App\Services\Resque\QueueService;
use App\Services\TaskTypeService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use App\Tests\Services\TestHttpClientService;

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
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = self::$container->get(JobFactory::class);

        $this->command = self::$container->get(ResolveWebsiteCommand::class);
        $this->httpClientService = self::$container->get(HttpClientService::class);
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
            Job::STATE_CANCELLED => [
                'stateName' => Job::STATE_CANCELLED,
            ],
            Job::STATE_COMPLETED => [
                'stateName' => Job::STATE_COMPLETED,
            ],
            Job::STATE_IN_PROGRESS => [
                'stateName' => Job::STATE_IN_PROGRESS,
            ],
            Job::STATE_PREPARING => [
                'stateName' => Job::STATE_PREPARING,
            ],
            Job::STATE_QUEUED => [
                'stateName' => Job::STATE_QUEUED,
            ],
            Job::STATE_FAILED_NO_SITEMAP => [
                'stateName' => Job::STATE_FAILED_NO_SITEMAP,
            ],
            Job::STATE_REJECTED => [
                'stateName' => Job::STATE_REJECTED,
            ],
            Job::STATE_RESOLVING => [
                'stateName' => Job::STATE_RESOLVING,
            ],
            Job::STATE_RESOLVED => [
                'stateName' => Job::STATE_RESOLVED,
            ],
        ];
    }

    public function testRunIsRejected()
    {
        $job = $this->jobFactory->create();

        $curl28ConnectException = ConnectExceptionFactory::create(28, 'Operation timed out');

        $this->httpClientService->appendFixtures([
            $curl28ConnectException,
            $curl28ConnectException,
            $curl28ConnectException,
            $curl28ConnectException,
            $curl28ConnectException,
            $curl28ConnectException,
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_OK, $returnCode);
        $this->assertEquals(Job::STATE_REJECTED, $job->getState()->getName());
    }

    /**
     * @dataProvider runForSingleUrlJobDataProvider
     *
     * @param array $jobValues
     * @param array $expectedTaskParameters
     */
    public function testRunForSingleUrlJob($jobValues, $expectedTaskParameters)
    {
        $resqueQueueService = self::$container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $jobValues[JobFactory::KEY_TYPE] = JobTypeService::SINGLE_URL_NAME;

        $job = $this->jobFactory->create($jobValues);

        $this->httpClientService->appendFixtures([
            new Response(),
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_OK, $returnCode);
        $this->assertEquals(Job::STATE_QUEUED, $job->getState()->getName());

        /* @var Task[] $tasks */
        $tasks = $job->getTasks()->toArray();

        foreach ($tasks as $taskIndex => $task) {
            $this->assertEquals($expectedTaskParameters[$taskIndex], $task->getParameters()->getAsArray());
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
                            'maxcdn.bootstrapcdn.com',
                            'use.fontawesome.com',
                        ],
                        'ignore-common-cdns' =>  true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider runForFullSiteJobDataProvider
     *
     * @param array $jobValues
     * @param array $additionalArgs
     */
    public function testRunForFullSiteJob(array $jobValues, array $additionalArgs)
    {
        $resqueQueueService = self::$container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('job-prepare')->clear();

        $job = $this->jobFactory->create($jobValues);

        $this->httpClientService->appendFixtures([
            new Response(),
        ]);

        $commandArgs = array_merge($additionalArgs, ['id' => $job->getId()]);

        $returnCode = $this->command->run(new ArrayInput($commandArgs), new BufferedOutput());


        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_OK, $returnCode);
        $this->assertEquals(Job::STATE_RESOLVED, $job->getState()->getName());

        $this->assertTrue($resqueQueueService->contains(
            'job-prepare',
            ['id' => $job->getId()]
        ));
    }

    public function runForFullSiteJobDataProvider(): array
    {
        return [
            'default' => [
                'jobValues' => [],
                'additionalArgs' => [],
            ],
            'job in wrong state, reset state' => [
                'jobValues' => [
                    JobFactory::KEY_STATE => Job::STATE_RESOLVING,
                ],
                'additionalArgs' => ['--reset-state' => true],
            ],
        ];
    }

    public function testRunForMultipleJobs()
    {
        $jobValuesCollection = [
            [
                JobFactory::KEY_URL => 'http://one.example.com',
            ],
            [
                JobFactory::KEY_URL => 'http://two.example.com',
            ],
            [
                JobFactory::KEY_URL => 'http://three.example.com',
            ],
        ];

        $resqueQueueService = self::$container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('job-prepare')->clear();

        /* @var Job[] $jobs */
        $jobs = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobs[] = $this->jobFactory->create($jobValues);
        }

        $jobIds = [];

        foreach ($jobs as $job) {
            $jobIds[] = $job->getId();
        }

        $this->httpClientService->appendFixtures([
            new Response(),
            new Response(),
            new Response(),
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => implode(',', $jobIds),
        ]), new BufferedOutput());

        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_OK, $returnCode);

        foreach ($jobs as $job) {
            $this->assertEquals(Job::STATE_RESOLVED, $job->getState()->getName());
            $this->assertTrue($resqueQueueService->contains(
                'job-prepare',
                ['id' => $job->getId()]
            ));
        }
    }
}
