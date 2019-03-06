<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use App\Entity\Task\Task;
use App\Services\TaskTypeService;
use App\Tests\Services\JobFactory;

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
                'taskStateName' => Task::STATE_COMPLETED,
                'expectedUrls' => [],
            ],
            'found' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                    JobFactory::KEY_TASKS => [
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                        ],
                        [
                            JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                        ],
                    ],
                ],
                'taskStateName' => Task::STATE_COMPLETED,
                'expectedUrls' => [
                    'http://example.com/one',
                    'http://example.com/foo bar',
                ],
            ],
        ];
    }
}
