<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use App\Entity\Task\Task;
use App\Entity\Job\Job;
use App\Services\TaskTypeService;
use App\Tests\Services\JobFactory;
use App\Tests\Services\TaskFactory;

trait GetIdsByStateDataProvider
{
    /**
     * @return array
     */
    public function getIdsByStateDataProvider()
    {
        return [
            'completed' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
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
                                TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                        ],
                    ],
                ],
                'taskStateName' => Task::STATE_COMPLETED,
                'expectedTaskIndices' => [0, 1],
            ],
            'cancelled' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
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
                                TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                        ],
                    ],
                ],
                'taskStateName' => Task::STATE_CANCELLED,
                'expectedTaskIndices' => [3, 4, 5],
            ],
        ];
    }
}
