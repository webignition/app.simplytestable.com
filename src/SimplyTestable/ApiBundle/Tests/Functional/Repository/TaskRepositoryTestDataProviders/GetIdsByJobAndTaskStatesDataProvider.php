<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

trait GetIdsByJobAndTaskStatesDataProvider
{
    /**
     * @return array
     */
    public function getIdsByJobAndTaskStatesDataProvider()
    {
        return [
            'job zero' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::QUEUED_STATE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                        ],
                    ],
                ],
                'jobIndex' => 0,
                'limit' => 0,
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::CANCELLED_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                        ],
                    ],
                ],
                'jobIndex' => 1,
                'limit' => 0,
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::CANCELLED_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                        ],
                    ],
                ],
                'jobIndex' => 0,
                'limit' => 3,
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE
                ],
                'expectedTaskIds' => [0, 1, 2],
            ],
        ];
    }
}
