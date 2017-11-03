<?php

namespace Tests\ApiBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\JobFactory;

trait FindUrlsByJobAndStateDataProvider
{
    /**
     * @return array
     */
    public function findUrlsByJobAndStateDataProvider()
    {
        return [
            'none found' => [
                'jobValues' => [],
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedUrls' => [],
            ],
            'found' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => TaskService::CANCELLED_STATE,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => TaskService::COMPLETED_STATE,
                        ],
                    ],
                ],
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedUrls' => [
                    'http://example.com/one',
                    'http://example.com/foo bar',
                ],
            ],
        ];
    }
}
