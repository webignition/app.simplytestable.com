<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use App\Entity\Task\Task;
use App\Entity\Job\Job;
use App\Services\TaskTypeService;
use App\Tests\Services\JobFactory;
use App\Tests\Services\TaskFactory;

trait GetCountByJobAndStatesDataProvider
{
    /**
     * @return array
     */
    public function getCountByJobAndStatesDataProvider()
    {
        return [
            'first job' => [
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
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
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
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                ],
                'jobIndex' => 0,
                'taskStateNames' => [
                    Task::STATE_COMPLETED,
                    Task::STATE_QUEUED,
                ],
                'expectedCount' => 4,
            ],
            'second job' => [
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
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
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
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_QUEUED,
                            ],
                        ],
                    ],
                ],
                'jobIndex' => 1,
                'taskStateNames' => [
                    Task::STATE_COMPLETED,
                    Task::STATE_CANCELLED,
                ],
                'expectedCount' => 2,
            ],
        ];
    }
}
