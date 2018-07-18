<?php

namespace Tests\AppBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use AppBundle\Entity\Task\Task;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\JobFactory;

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
