<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

trait GetCountByJobAndStateDataProvider
{
    /**
     * @return array
     */
    public function getCountByJobAndStateDataProvider()
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
                            TaskService::COMPLETED_STATE,
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
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::CANCELLED_STATE,
                        ],
                    ],
                ],
                'jobIndex' => 0,
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedCount' => 3,
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
                            TaskService::COMPLETED_STATE,
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
                            TaskService::COMPLETED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::QUEUED_STATE,
                        ],
                    ],
                ],
                'jobIndex' => 1,
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedCount' => 2,
            ],
        ];
    }
}
