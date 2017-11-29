<?php

namespace Tests\ApiBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\JobFactory;

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
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
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
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
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
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
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
                                JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => TaskService::QUEUED_STATE,
                            ],
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
