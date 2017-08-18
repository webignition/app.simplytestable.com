<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;

trait GetErroredCountByJobDataProvider
{
    /**
     * @return array
     */
    public function getErroredCountByJobDataProvider()
    {
        return [
            'job zero, no state exclusion' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 3,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                ],
                'jobIndex' => 0,
                'stateNamesToExclude' => [

                ],
                'expectedErroredCount' => 3,
            ],
            'job one, no state exclusion' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 3,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                ],
                'jobIndex' => 1,
                'stateNamesToExclude' => [

                ],
                'expectedErroredCount' => 1,
            ],
            'job zero, with state exclusion' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::COMPLETED_STATE,
                            TaskService::CANCELLED_STATE,
                            TaskService::QUEUED_STATE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 3,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                ],
                'jobIndex' => 0,
                'stateNamesToExclude' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'expectedErroredCount' => 1,
            ],
        ];
    }
}
