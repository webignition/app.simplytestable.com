<?php

namespace Tests\AppBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use AppBundle\Entity\Task\Task;
use AppBundle\Entity\Job\Job;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\JobFactory;

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
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
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
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
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
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
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
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
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