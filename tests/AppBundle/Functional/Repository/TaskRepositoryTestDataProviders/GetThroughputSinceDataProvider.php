<?php

namespace Tests\AppBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use Tests\AppBundle\Factory\JobFactory;

trait GetThroughputSinceDataProvider
{
    /**
     * @return array
     */
    public function getThroughputSinceDataProvider()
    {
        return [
            'no tasks' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskEndDateTimeCollection' => [],
                'sinceDateTime' => new \DateTime(),
                'expectedThroughput' => 0,
            ],
            'none matching' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskEndDateTimeCollection' => [
                    new \DateTime('-1 hour'),
                    new \DateTime('-1 hour'),
                    new \DateTime('-1 hour'),
                ],
                'sinceDateTime' => new \DateTime('-1 minute'),
                'expectedThroughput' => 0,
            ],
            'all matching, one job' => [
                'jobValuesCollection' => [
                    [],
                ],
                'taskEndDateTimeCollection' => [
                    new \DateTime('-1 minute'),
                    new \DateTime('-1 minute'),
                    new \DateTime('-1 minute'),
                ],
                'sinceDateTime' => new \DateTime('-2 minute'),
                'expectedThroughput' => 3,
            ],
            'all matching, multiple jobs' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'taskEndDateTimeCollection' => [
                    new \DateTime('-1 minute'),
                    new \DateTime('-1 minute'),
                    new \DateTime('-1 minute'),
                    new \DateTime('-1 minute'),
                    new \DateTime('-1 minute'),
                    new \DateTime('-1 minute'),
                ],
                'sinceDateTime' => new \DateTime('-2 minute'),
                'expectedThroughput' => 6,
            ],
            'some matching, multiple jobs' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'taskEndDateTimeCollection' => [
                    new \DateTime('-1 minute'),
                    new \DateTime('-3 minute'),
                    new \DateTime('-3 minute'),
                    new \DateTime('-3 minute'),
                    new \DateTime('-3 minute'),
                    new \DateTime('-1 minute'),
                ],
                'sinceDateTime' => new \DateTime('-2 minute'),
                'expectedThroughput' => 2,
            ],
        ];
    }
}
