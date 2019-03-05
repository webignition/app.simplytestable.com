<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Command\Job;

use App\Entity\Job\TaskTypeOptions;
use App\Repository\JobRepository;
use App\Repository\TaskRepository;
use App\Services\TaskTypeService;
use Doctrine\ORM\EntityManagerInterface;
use App\Command\Job\DeleteCommand;
use App\Entity\Job\Job;
use App\Tests\Factory\JobFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DeleteCommandTest extends AbstractBaseTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DeleteCommand
     */
    private $deleteCommand;

    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory(self::$container);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->deleteCommand = self::$container->get(DeleteCommand::class);
        $this->jobRepository = self::$container->get(JobRepository::class);
    }

    /**
     * @dataProvider runDataProvider
     */
    public function testRun(
        array $jobValuesCollection,
        int $jobIndexToDelete,
        array $expectedJobValues,
        array $expectedRemainingJobValuesCollection
    ) {
        $taskRepository = self::$container->get(TaskRepository::class);
        $jobTaskTypeOptionsRepository = $this->entityManager->getRepository(TaskTypeOptions::class);

        /* @var Job[] $jobs */
        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

        $expectedJobExists = !empty($expectedJobValues);

        $job = null;
        if ($expectedJobExists) {
            $job = $jobs[$jobIndexToDelete];

            $this->assertInstanceOf(Job::class, $job);
            $this->assertNotEmpty($taskRepository->findBy([
                'job' => $job,
            ]));

            $jobTaskTypeOptionsCollection = $jobTaskTypeOptionsRepository->findBy([
                'job' => $job,
            ]);

            if (empty($expectedJobValues[JobFactory::KEY_TEST_TYPE_OPTIONS])) {
                $this->assertEmpty($jobTaskTypeOptionsCollection);
            } else {
                foreach ($jobTaskTypeOptionsCollection as $jobTaskTypeOptions) {
                    $taskType = $jobTaskTypeOptions->getTaskType();
                    $taskTypeName = $taskType->getName();

                    $expectedTaskTypeOptions = $expectedJobValues[JobFactory::KEY_TEST_TYPE_OPTIONS][$taskTypeName];

                    $this->assertEquals($expectedTaskTypeOptions, $jobTaskTypeOptions->getOptions());
                }
            }
        }

        $jobId = empty($job)
            ? -1
            : $job->getId();

        $returnCode = $this->deleteCommand->run(new ArrayInput([
            'id' => $jobId,
            '--force' => true,
        ]), new BufferedOutput());

        $this->assertEquals(DeleteCommand::RETURN_CODE_OK, $returnCode);
        $this->assertNull($this->jobRepository->find($jobId));

        if ($expectedJobExists) {
            $this->assertEmpty($taskRepository->findBy([
                'job' => $job,
            ]));

            $this->assertEmpty($jobTaskTypeOptionsRepository->findBy([
                'job' => $job,
            ]));
        }

        $remainingJobs = $this->jobRepository->findAll();

        foreach ($remainingJobs as $jobIndex => $remainingJob) {
            $expectedRemainingJobValues = $expectedRemainingJobValuesCollection[$jobIndex];

            $this->assertEquals(
                $expectedRemainingJobValues[JobFactory::KEY_SITE_ROOT_URL],
                (string)$remainingJob->getWebsite()
            );
        }
    }

    public function runDataProvider(): array
    {
        return [
            'job not found; no jobs' => [
                'jobValuesCollection' => [],
                'jobIndexToDelete' => 0,
                'expectedJobValues' => [],
                'expectedRemainingJobValuesCollection' => [],
            ],
            'job not found; job does not exist' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                    ],
                ],
                'jobIndexToDelete' => 1,
                'expectedJobValues' => [],
                'expectedRemainingJobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                    ],
                ],
            ],
            'job exists, no task type options' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://one.example.com/',
                        JobFactory::KEY_DOMAIN => 'one.example.com',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://two.example.com/',
                        JobFactory::KEY_DOMAIN => 'two.example.com',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://three.example.com/',
                        JobFactory::KEY_DOMAIN => 'three.example.com',
                    ],
                ],
                'jobIndexToDelete' => 1,
                'expectedJobValues' => [
                    JobFactory::KEY_SITE_ROOT_URL => 'http://two.example.com/',
                    JobFactory::KEY_TEST_TYPE_OPTIONS => [],
                ],
                'expectedRemainingJobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://one.example.com/',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://three.example.com/',
                    ],
                ],
            ],
            'job exists, has task type options' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://one.example.com/',
                        JobFactory::KEY_DOMAIN => 'one.example.com',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_TEST_TYPE_OPTIONS => [
                            TaskTypeService::CSS_VALIDATION_TYPE => [
                                'domains-to-ignore' => [
                                    'foo',
                                ],
                                'ignore-common-cdns' => true,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://two.example.com/',
                        JobFactory::KEY_DOMAIN => 'two.example.com',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://three.example.com/',
                        JobFactory::KEY_DOMAIN => 'three.example.com',
                    ],
                ],
                'jobIndexToDelete' => 0,
                'expectedJobValues' => [
                    JobFactory::KEY_SITE_ROOT_URL => 'http://one.example.com/',
                    JobFactory::KEY_TEST_TYPE_OPTIONS => [
                        TaskTypeService::CSS_VALIDATION_TYPE => [
                            'domains-to-ignore' => [
                                'foo',
                            ],
                            'ignore-common-cdns' => true,
                        ],
                    ],
                ],
                'expectedRemainingJobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://two.example.com/',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://three.example.com/',
                    ],
                ],
            ],
        ];
    }
}
