<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

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
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
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
                    TaskService::CANCELLED_STATE,
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
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
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
                    TaskService::CANCELLED_STATE,
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
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
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
                    TaskService::CANCELLED_STATE,
                    TaskService::COMPLETED_STATE,
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
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::AWAITING_CANCELLATION_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::TASK_SKIPPED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::IN_PROGRESS_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'leader',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'member1',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'member2',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::IN_PROGRESS_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
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
                    TaskService::IN_PROGRESS_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::QUEUED_STATE,
                ],
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-01-31 23:59:59',
                'expectedCount' => 8,
            ],
        ];
    }
}
