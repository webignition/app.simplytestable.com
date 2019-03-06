<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use App\Entity\Task\Task;
use App\Entity\Job\Job;
use App\Services\TaskTypeService;
use App\Tests\Services\JobFactory;

trait GetIdsByJobAndStatesDataProvider
{
    /**
     * @return array
     */
    public function getIdsByJobAndStatesDataProvider()
    {
        return [
            'job zero' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
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
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
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
                'jobIndex' => 0,
                'limit' => 0,
                'taskStateNames' => [
                    Task::STATE_COMPLETED,
                    Task::STATE_FAILED_RETRY_AVAILABLE
                ],
                'expectedTaskIds' => [0, 1],
            ],
            'job one' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                        ],
                    ],
                ],
                'jobIndex' => 1,
                'limit' => 0,
                'taskStateNames' => [
                    Task::STATE_COMPLETED,
                    Task::STATE_FAILED_RETRY_AVAILABLE
                ],
                'expectedTaskIds' => [3, 5],
            ],
            'job zero, limit three' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                        ],
                    ],
                ],
                'jobIndex' => 0,
                'limit' => 2,
                'taskStateNames' => [
                    Task::STATE_COMPLETED,
                    Task::STATE_FAILED_RETRY_AVAILABLE
                ],
                'expectedTaskIds' => [0, 1],
            ],
        ];
    }
}
