<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use App\Entity\Task\Task;
use App\Tests\Services\JobFactory;

trait GetCountByUsersAndStateForPeriodDataProvider
{
    /**
     * @return array
     */
    public function getCountByUsersAndStatesForPeriodDataProvider()
    {
        return [
            'single job, single user, single state, no matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                        ],
                    ],
                ],
                'taskEndDateTimeCollection' => [
                    new \DateTime('2017-01-01 10:00:00'),
                    new \DateTime('2017-01-02 10:00:00'),
                    new \DateTime('2017-01-03 10:00:00'),
                ],
                'userNames' => [
                    'public',
                ],
                'stateNames' => [
                    Task::STATE_CANCELLED,
                ],
                'periodStart' => '2017-02-01',
                'periodEnd' => '2017-02-28 23:59:59',
                'expectedCount' => 0,
            ],
            'single job, single user, single state, with matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                        ],
                    ],
                ],
                'taskEndDateTimeCollection' => [
                    new \DateTime('2017-01-01 10:00:00'),
                    new \DateTime('2017-01-02 10:00:00'),
                    new \DateTime('2017-01-03 10:00:00'),
                ],
                'userNames' => [
                    'public',
                ],
                'stateNames' => [
                    Task::STATE_CANCELLED,
                ],
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-01-31 23:59:59',
                'expectedCount' => 1,
            ],
            'single job, single user, multiple states, with matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                        ],
                    ],
                ],
                'taskEndDateTimeCollection' => [
                    new \DateTime('2017-01-01 10:00:00'),
                    new \DateTime('2017-01-02 10:00:00'),
                    new \DateTime('2017-01-03 10:00:00'),
                ],
                'userNames' => [
                    'public',
                ],
                'stateNames' => [
                    Task::STATE_CANCELLED,
                    Task::STATE_COMPLETED,
                ],
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-01-31 23:59:59',
                'expectedCount' => 2,
            ],
            'multiple users, multiple jobs, multiple states' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_AWAITING_CANCELLATION,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_SKIPPED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_IN_PROGRESS,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'leader',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'member1',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'member2',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_IN_PROGRESS,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                        ],
                    ],
                ],
                'taskEndDateTimeCollection' => [
                    new \DateTime('2017-01-01 10:00:00'),
                    new \DateTime('2017-01-02 10:00:00'),
                    new \DateTime('2017-01-03 10:00:00'),
                    new \DateTime('2017-01-01 10:00:00'),
                    new \DateTime('2017-01-02 10:00:00'),
                    new \DateTime('2017-01-03 10:00:00'),
                    new \DateTime('2017-01-01 10:00:00'),
                    new \DateTime('2017-01-02 10:00:00'),
                    new \DateTime('2017-01-03 10:00:00'),
                    new \DateTime('2017-01-01 10:00:00'),
                    new \DateTime('2017-01-02 10:00:00'),
                    new \DateTime('2017-01-03 10:00:00'),
                    new \DateTime('2017-01-01 10:00:00'),
                    new \DateTime('2017-01-02 10:00:00'),
                    new \DateTime('2017-01-03 10:00:00'),
                ],
                'userNames' => [
                    'leader',
                    'member1',
                    'member2',
                ],
                'stateNames' => [
                    Task::STATE_IN_PROGRESS,
                    Task::STATE_COMPLETED,
                    Task::STATE_QUEUED,
                ],
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-01-31 23:59:59',
                'expectedCount' => 8,
            ],
        ];
    }
}
