<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;

trait GetCountWithIssuesByJobDataProvider
{
    /**
     * @return array
     */
    public function getCountWithIssuesByJobDataProvider()
    {
        $jobValuesCollection = [
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
        ];

        $taskOutputValuesCollection = [
            [
                TaskOutputFactory::KEY_ERROR_COUNT => 3,
                TaskOutputFactory::KEY_WARNING_COUNT => 0,
            ],
            [
                TaskOutputFactory::KEY_ERROR_COUNT => 2,
                TaskOutputFactory::KEY_WARNING_COUNT => 1,
            ],
            [
                TaskOutputFactory::KEY_ERROR_COUNT => 1,
                TaskOutputFactory::KEY_WARNING_COUNT => 1,
            ],
            [
                TaskOutputFactory::KEY_ERROR_COUNT => 0,
                TaskOutputFactory::KEY_WARNING_COUNT => 0,
            ],
            [
                TaskOutputFactory::KEY_ERROR_COUNT => 0,
                TaskOutputFactory::KEY_WARNING_COUNT => 1,
            ],
            [
                TaskOutputFactory::KEY_ERROR_COUNT => 1,
                TaskOutputFactory::KEY_WARNING_COUNT => 0,
            ],
        ];

        return [
            'job zero, count with errors, no state exclusion' => [
                'jobValuesCollection' =>  $jobValuesCollection,
                'taskOutputValuesCollection' => $taskOutputValuesCollection,
                'jobIndex' => 0,
                'issueType' => TaskRepository::ISSUE_TYPE_ERROR,
                'stateNamesToExclude' => [],
                'expectedCount' => 3,
            ],
            'job zero, count with warnings, no state exclusion' => [
                'jobValuesCollection' =>  $jobValuesCollection,
                'taskOutputValuesCollection' => $taskOutputValuesCollection,
                'jobIndex' => 0,
                'issueType' => TaskRepository::ISSUE_TYPE_WARNING,
                'stateNamesToExclude' => [],
                'expectedCount' => 2,
            ],
            'job one, count with errors, no state exclusion' => [
                'jobValuesCollection' =>  $jobValuesCollection,
                'taskOutputValuesCollection' => $taskOutputValuesCollection,
                'jobIndex' => 1,
                'issueType' => TaskRepository::ISSUE_TYPE_ERROR,
                'stateNamesToExclude' => [],
                'expectedCount' => 1,
            ],
            'job one, count with warnings, no state exclusion' => [
                'jobValuesCollection' =>  $jobValuesCollection,
                'taskOutputValuesCollection' => $taskOutputValuesCollection,
                'jobIndex' => 1,
                'issueType' => TaskRepository::ISSUE_TYPE_WARNING,
                'stateNamesToExclude' => [],
                'expectedCount' => 1,
            ],
            'job zero, count with errors, with state exclusion' => [
                'jobValuesCollection' =>  $jobValuesCollection,
                'taskOutputValuesCollection' => $taskOutputValuesCollection,
                'jobIndex' => 0,
                'issueType' => TaskRepository::ISSUE_TYPE_ERROR,
                'stateNamesToExclude' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'expectedCount' => 1,
            ],
            'job zero, count with warnings, with state exclusion' => [
                'jobValuesCollection' =>  $jobValuesCollection,
                'taskOutputValuesCollection' => $taskOutputValuesCollection,
                'jobIndex' => 0,
                'issueType' => TaskRepository::ISSUE_TYPE_WARNING,
                'stateNamesToExclude' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'expectedCount' => 0,
            ],
        ];
    }
}