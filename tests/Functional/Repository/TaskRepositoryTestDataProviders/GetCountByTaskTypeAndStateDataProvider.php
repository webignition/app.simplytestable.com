<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Services\TaskTypeService;
use App\Tests\Services\JobFactory;
use App\Tests\Services\TaskFactory;

trait GetCountByTaskTypeAndStateDataProvider
{
    /**
     * @return array
     */
    public function getCountByTaskTypeAndStateDataProvider()
    {
        return [
            'none' => [
                'jobValuesCollection' => [],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateName' => Task::STATE_COMPLETED,
                'expectedCount' => 0,
            ],
            'many' => [
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
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
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
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                        JobFactory::KEY_TASKS => [
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                TaskFactory::KEY_STATE => Task::STATE_COMPLETED,
                            ],
                        ],
                    ],
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateName' => Task::STATE_COMPLETED,
                'expectedCount' => 6,
            ],
        ];
    }
}
