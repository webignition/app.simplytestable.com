<?php

namespace Tests\ApiBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\TaskOutputFactory;

trait GetOutputCollectionByJobAndStateDataProvider
{
    /**
     * @return array
     */
    public function getOutputCollectionByJobAndStateDataProvider()
    {
        return [
            'job zero' => [
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
                        TaskOutputFactory::KEY_OUTPUT => 'public-foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'public-bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'public-foobar',
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'private-foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'private-bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'private-foobar',
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                ],
                'jobIndex' => 0,
                'taskStateName' => Task::STATE_QUEUED,
                'expectedTaskOutputValues' => [
                    'public-foo',
                    'public-bar',
                ],
            ],
            'job one' => [
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
                        TaskOutputFactory::KEY_OUTPUT => 'public-foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'public-bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'public-foobar',
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'private-foo',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'private-bar',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'private-foobar',
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                ],
                'jobIndex' => 1,
                'taskStateName' => Task::STATE_QUEUED,
                'expectedTaskOutputValues' => [
                    'private-foo',
                    'private-bar',
                ],
            ],
        ];
    }
}
