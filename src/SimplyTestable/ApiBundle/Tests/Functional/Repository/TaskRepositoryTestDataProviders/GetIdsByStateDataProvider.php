<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::QUEUED_STATE,
                            TaskService::CANCELLED_STATE,
                            TaskService::CANCELLED_STATE,
                            TaskService::CANCELLED_STATE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                        ],
                    ],
                ],
                'taskStateName' => TaskService::COMPLETED_STATE,
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::QUEUED_STATE,
                            TaskService::CANCELLED_STATE,
                            TaskService::CANCELLED_STATE,
                            TaskService::CANCELLED_STATE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                        ],
                    ],
                ],
                'taskStateName' => TaskService::CANCELLED_STATE,
                'expectedTaskIndices' => [3, 4, 5],
            ],
        ];
    }
}
