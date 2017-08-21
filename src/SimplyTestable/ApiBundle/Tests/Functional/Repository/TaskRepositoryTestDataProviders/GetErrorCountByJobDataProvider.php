<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;

trait GetErrorCountByJobDataProvider
{
    /**
     * @return array
     */
    public function getErrorCountByJobDataProvider()
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
                'expectedErrorCount' => 6,
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
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_ERROR_COUNT => 1,
                    ],
                ],
                'jobIndex' => 1,
                'expectedErrorCount' => 2,
            ],
        ];
    }
}
