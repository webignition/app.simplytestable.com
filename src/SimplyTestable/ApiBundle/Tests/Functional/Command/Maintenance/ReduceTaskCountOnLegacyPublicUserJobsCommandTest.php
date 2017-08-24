<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Maintenance;

use SimplyTestable\ApiBundle\Command\Maintenance\ReduceTaskCountOnLegacyPublicUserJobsCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobAmmendmentFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ReduceTaskCountOnLegacyPublicUserJobsCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var ReduceTaskCountOnLegacyPublicUserJobsCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(
            'simplytestable.command.maintenance.reducetaskcountonlegacypublicuserjobs'
        );
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $jobTaskCounts
     * @param array $args
     * @param array $expectedAffectedJobIndices
     * @param array $expectedAffectedJobAmmendments
     * @param array $expectedAffectedJobTaskUrls
     */
    public function testRun(
        $jobValuesCollection,
        $jobTaskCounts,
        $args,
        $expectedAffectedJobIndices,
        $expectedAffectedJobAmmendments,
        $expectedAffectedJobTaskUrls
    ) {
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobFactory = new JobFactory($this->container);

        $jobs = [];
        $htmlValidationTaskType = $taskTypeService->getByName(TaskTypeService::HTML_VALIDATION_TYPE);
        $taskCompletedState = $stateService->fetch(TaskService::COMPLETED_STATE);

        foreach ($jobValuesCollection as $jobValuesIndex => $jobValues) {
            $job = $jobFactory->create($jobValues);

            $taskCount = $jobTaskCounts[$jobValuesIndex];

            for ($i = 0; $i < $taskCount; $i++) {
                $url = $jobValues[JobFactory::KEY_SITE_ROOT_URL] . $i;

                $task = new Task();
                $task->setUrl($url);
                $task->setType($htmlValidationTaskType);
                $task->setState($taskCompletedState);
                $task->setJob($job);

                $job->getTasks()->add($task);

                $entityManager->persist($task);
            }

            $entityManager->persist($job);
            $jobs[] = $job;
        }

        $entityManager->flush();

        if (isset($args['--job-ids-to-ignore'])) {
            $jobIndices = explode(',', $args['--job-ids-to-ignore']);
            $jobIds = [];

            foreach ($jobs as $jobIndex => $job) {
                if (in_array($jobIndex, $jobIndices)) {
                    $jobIds[] = $job->getId();
                }

                $args['--job-ids-to-ignore'] = implode(',', $jobIds);
            }
        }

        $returnCode = $this->command->run(new ArrayInput($args), new BufferedOutput());

        $this->assertEquals(
            ReduceTaskCountOnLegacyPublicUserJobsCommand::RETURN_CODE_OK,
            $returnCode
        );

        /* @var Job[] $expectedAffectedJobs */
        $expectedAffectedJobs = [];
        foreach ($jobs as $jobIndex => $job) {
            if (in_array($jobIndex, $expectedAffectedJobIndices)) {
                $expectedAffectedJobs[] = $job;
            }
        }

        foreach ($expectedAffectedJobs as $affectedJobIndex => $affectedJob) {
            $expectedAmmendmentValuesCollection = $expectedAffectedJobAmmendments[$affectedJobIndex];

            foreach ($affectedJob->getAmmendments() as $ammendmentIndex => $ammendment) {
                $expectedAmmentmentValues = $expectedAmmendmentValuesCollection[$ammendmentIndex];

                $this->assertEquals(
                    $expectedAmmentmentValues,
                    [
                        'reason' => $ammendment->getReason(),
                    ]
                );
            }
        }

        foreach ($expectedAffectedJobTaskUrls as $affectedJobIndex => $affectedJobTaskUrls) {
            $job = $expectedAffectedJobs[$affectedJobIndex];

            foreach ($job->getTasks() as $taskIndex => $task) {
                $this->assertEquals($affectedJobTaskUrls[$taskIndex], $task->getUrl());
            }
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'single affected job, has existing plan url limit reached ammendment' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo1.example.com/',
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo2.example.com/',
                        JobFactory::KEY_AMMENDMENTS => [
                            [
                                JobAmmendmentFactory::KEY_REASON => 'plan-url-limit-reached:discovered-url-count-' . 12,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo3.example.com/',
                        JobFactory::KEY_STATE => JobService::FAILED_NO_SITEMAP_STATE,
                    ],
                ],
                'jobTaskCounts' => [
                    1, 20, 3,
                ],
                'args' => [
                    '--task-removal-group-size' => 3,
                ],
                'expectedAffectedJobIndices' => [1],
                'expectedAffectedJobAmmendments' => [
                    [
                        [
                            JobAmmendmentFactory::KEY_REASON => 'plan-url-limit-reached:discovered-url-count-' . 12,
                        ],
                    ],
                ],
                'expectedAffectedJobTaskUrls' => [
                    [
                        'http://foo2.example.com/0',
                        'http://foo2.example.com/1',
                        'http://foo2.example.com/2',
                        'http://foo2.example.com/3',
                        'http://foo2.example.com/4',
                        'http://foo2.example.com/5',
                        'http://foo2.example.com/6',
                        'http://foo2.example.com/7',
                        'http://foo2.example.com/8',
                        'http://foo2.example.com/9',
                    ],
                ],
            ],
            'single affected job, has existing non-relevant ammendment' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo1.example.com/',
                        JobFactory::KEY_AMMENDMENTS => [
                            [
                                JobAmmendmentFactory::KEY_REASON => 'foo',
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo2.example.com/',
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo3.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                ],
                'jobTaskCounts' => [
                    14, 3, 3,
                ],
                'args' => [
                    '--task-removal-group-size' => 3,
                ],
                'expectedAffectedJobIndices' => [0],
                'expectedAffectedJobAmmendments' => [
                    [
                        [
                            JobAmmendmentFactory::KEY_REASON => 'foo',
                        ],
                        [
                            JobAmmendmentFactory::KEY_REASON => 'plan-url-limit-reached:discovered-url-count-' . 14,
                        ],
                    ],
                ],
                'expectedAffectedJobTaskUrls' => [
                    [
                        'http://foo1.example.com/0',
                        'http://foo1.example.com/1',
                        'http://foo1.example.com/2',
                        'http://foo1.example.com/3',
                        'http://foo1.example.com/4',
                        'http://foo1.example.com/5',
                        'http://foo1.example.com/6',
                        'http://foo1.example.com/7',
                        'http://foo1.example.com/8',
                        'http://foo1.example.com/9',
                    ],
                ],
            ],
            'single affected job, no previous ammendments' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo1.example.com/',
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo2.example.com/',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo3.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                ],
                'jobTaskCounts' => [
                    3, 11, 3,
                ],
                'args' => [
                    '--task-removal-group-size' => 3,
                ],
                'expectedAffectedJobIndices' => [1],
                'expectedAffectedJobAmmendments' => [
                    [
                        [
                            JobAmmendmentFactory::KEY_REASON => 'plan-url-limit-reached:discovered-url-count-' . 11,
                        ],
                    ],
                ],
                'expectedAffectedJobTaskUrls' => [
                    [
                        'http://foo2.example.com/0',
                        'http://foo2.example.com/1',
                        'http://foo2.example.com/2',
                        'http://foo2.example.com/3',
                        'http://foo2.example.com/4',
                        'http://foo2.example.com/5',
                        'http://foo2.example.com/6',
                        'http://foo2.example.com/7',
                        'http://foo2.example.com/8',
                        'http://foo2.example.com/9',
                    ],
                ],
            ],
            'single affected job, no previous ammendments, is below url limit' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo1.example.com/',
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo2.example.com/',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo3.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                ],
                'jobTaskCounts' => [
                    3, 9, 3,
                ],
                'args' => [
                    '--task-removal-group-size' => 3,
                ],
                'expectedAffectedJobIndices' => [1],
                'expectedAffectedJobAmmendments' => [
                    [],
                ],
                'expectedAffectedJobTaskUrls' => [
                    [
                        'http://foo2.example.com/0',
                        'http://foo2.example.com/1',
                        'http://foo2.example.com/2',
                        'http://foo2.example.com/3',
                        'http://foo2.example.com/4',
                        'http://foo2.example.com/5',
                        'http://foo2.example.com/6',
                        'http://foo2.example.com/7',
                        'http://foo2.example.com/8',
                    ],
                ],
            ],
            'single matched job, is ignored via --job-ids-to-ignore' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo1.example.com/',
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo2.example.com/',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo3.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                ],
                'jobTaskCounts' => [
                    3, 11, 3,
                ],
                'args' => [
                    '--task-removal-group-size' => 3,
                    '--job-ids-to-ignore' => '1'
                ],
                'expectedAffectedJobIndices' => [1],
                'expectedAffectedJobAmmendments' => [
                    [
                        [
                            JobAmmendmentFactory::KEY_REASON => 'plan-url-limit-reached:discovered-url-count-' . 11,
                        ],
                    ],
                ],
                'expectedAffectedJobTaskUrls' => [
                    [
                        'http://foo2.example.com/0',
                        'http://foo2.example.com/1',
                        'http://foo2.example.com/2',
                        'http://foo2.example.com/3',
                        'http://foo2.example.com/4',
                        'http://foo2.example.com/5',
                        'http://foo2.example.com/6',
                        'http://foo2.example.com/7',
                        'http://foo2.example.com/8',
                        'http://foo2.example.com/9',
                        'http://foo2.example.com/10',
                    ],
                ],
            ],
            'single affected job, --dry-run' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo1.example.com/',
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo2.example.com/',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo3.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                ],
                'jobTaskCounts' => [
                    3, 11, 3,
                ],
                'args' => [
                    '--task-removal-group-size' => 3,
                    '--dry-run' => '1',
                ],
                'expectedAffectedJobIndices' => [1],
                'expectedAffectedJobAmmendments' => [
                    [],
                ],
                'expectedAffectedJobTaskUrls' => [
                    [
                        'http://foo2.example.com/0',
                        'http://foo2.example.com/1',
                        'http://foo2.example.com/2',
                        'http://foo2.example.com/3',
                        'http://foo2.example.com/4',
                        'http://foo2.example.com/5',
                        'http://foo2.example.com/6',
                        'http://foo2.example.com/7',
                        'http://foo2.example.com/8',
                        'http://foo2.example.com/9',
                        'http://foo2.example.com/10',
                    ],
                ],
            ],
        ];
    }
}
