<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;

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
                'taskStateName' => TaskService::QUEUED_STATE,
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
                'taskStateName' => TaskService::QUEUED_STATE,
                'expectedTaskOutputValues' => [
                    'private-foo',
                    'private-bar',
                ],
            ],
        ];
    }
}
