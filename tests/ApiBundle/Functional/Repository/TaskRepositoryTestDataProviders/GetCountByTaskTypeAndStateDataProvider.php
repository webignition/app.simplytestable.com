<?php

namespace Tests\ApiBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\JobFactory;

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
                'taskStateName' => TaskService::COMPLETED_STATE,
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
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
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
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
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
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
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
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedCount' => 6,
            ],
        ];
    }
}
