<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

trait GetTaskCountByStateDataProvider
{
    /**
     * @return array
     */
    public function getTaskCountByStateDataProvider()
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::QUEUED_STATE,
                            TaskService::QUEUED_STATE,
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
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::CANCELLED_STATE,
                        ],
                    ],
                ],
                'jobIndex' => 0,
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::QUEUED_STATE,
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::QUEUED_STATE,
                            TaskService::QUEUED_STATE,
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
                            TaskService::COMPLETED_STATE,
                            TaskService::CANCELLED_STATE,
                            TaskService::QUEUED_STATE,
                        ],
                    ],
                ],
                'jobIndex' => 1,
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'expectedCount' => 2,
            ],
        ];
    }
}