<?php

namespace Tests\ApiBundle\Functional\Command\Job;

use GuzzleHttp\Psr7\Response;
use SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\ConnectExceptionFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\ApiBundle\Services\TestHttpClientService;

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

        $this->jobFactory = new JobFactory($this->container);

        $this->command = $this->container->get(ResolveWebsiteCommand::class);
        $this->httpClientService = $this->container->get(HttpClientService::class);
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

        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out');

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
        $resqueQueueService = $this->container->get(QueueService::class);
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
        $resqueQueueService = $this->container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('job-prepare')->clear();

        $job = $this->jobFactory->create();

        $this->httpClientService->appendFixtures([
            new Response(),
        ]);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());


        $this->assertEquals(ResolveWebsiteCommand::RETURN_CODE_OK, $returnCode);
        $this->assertEquals(Job::STATE_RESOLVED, $job->getState()->getName());

        $this->assertTrue($resqueQueueService->contains(
            'job-prepare',
            ['id' => $job->getId()]
        ));
    }
}
