<?php

namespace Tests\AppBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Factory\TaskOutputFactory;

trait GetWarningCountByJobDataProvider
{
    /**
     * @return array
     */
    public function getWarningCountByJobDataProvider()
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
                        TaskOutputFactory::KEY_WARNING_COUNT => 3,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                ],
                'jobIndex' => 0,
                'expectedWarningCount' => 6,
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
                        TaskOutputFactory::KEY_WARNING_COUNT => 3,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 2,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 0,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                    [
                        TaskOutputFactory::KEY_WARNING_COUNT => 1,
                    ],
                ],
                'jobIndex' => 1,
                'expectedWarningCount' => 2,
            ],
        ];
    }
}
