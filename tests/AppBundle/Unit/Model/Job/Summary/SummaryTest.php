<?php

namespace Tests\AppBundle\Unit\Model\Job\Summary;

use AppBundle\Entity\Job\Ammendment;
use AppBundle\Entity\Job\Job;
use AppBundle\Entity\Job\RejectionReason;
use AppBundle\Entity\User;
use AppBundle\Model\Job\Summary\CrawlSummary;
use AppBundle\Model\Job\Summary\Summary;
use AppBundle\Services\JobTypeService;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\ModelFactory;

class SummaryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param Job $job
     * @param int $taskCount
     * @param array $taskCountByState
     * @param int $tasksWithErrorsCount
     * @param int $tasksWithWarningsCount
     * @param int $skippedTaskCount
     * @param int $cancelledTaskCount
     * @param bool $isPublic
     * @param int $errorCount
     * @param int $warningCount
     * @param User[] $owners
     * @param RejectionReason|null $rejectionReason
     * @param Ammendment[] $ammendments
     * @param CrawlSummary|null $crawlSummary
     * @param array $expectedSerializedData
     */
    public function testJsonSerialize(
        Job $job,
        $taskCount,
        $taskCountByState,
        $tasksWithErrorsCount,
        $tasksWithWarningsCount,
        $skippedTaskCount,
        $cancelledTaskCount,
        $isPublic,
        $errorCount,
        $warningCount,
        $owners,
        $rejectionReason,
        $ammendments,
        $crawlSummary,
        $expectedSerializedData
    ) {
        $jobSummary = new Summary(
            $job,
            $taskCount,
            $taskCountByState,
            $tasksWithErrorsCount,
            $tasksWithWarningsCount,
            $skippedTaskCount,
            $cancelledTaskCount,
            $isPublic,
            $errorCount,
            $warningCount,
            $owners
        );

        if (!empty($rejectionReason)) {
            $jobSummary->setRejectionReason($rejectionReason);
        }

        if (!empty($ammendments)) {
            $jobSummary->setAmmendments($ammendments);
        }

        if (!empty($crawlSummary)) {
            $jobSummary->setCrawlSummary($crawlSummary);
        }

        $this->assertEquals($expectedSerializedData, $jobSummary->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        $htmlValidationTaskType = ModelFactory::createTaskType([
            ModelFactory::TASK_TYPE_NAME => TaskTypeService::HTML_VALIDATION_TYPE,
        ]);

        $cssValidationTaskType = ModelFactory::createTaskType([
            ModelFactory::TASK_TYPE_NAME => TaskTypeService::CSS_VALIDATION_TYPE,
        ]);

        return [
            'no task types, no task type options, no parameters, no time period' => [
                'job' => ModelFactory::createJob([
                    ModelFactory::JOB_ID => 1,
                    ModelFactory::JOB_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                    ]),
                    ModelFactory::JOB_STATE => ModelFactory::createState('job-completed'),
                    ModelFactory::JOB_URL_COUNT => 12,
                    ModelFactory::JOB_REQUESTED_TASK_TYPES => [],
                    ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [],
                    ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                    ]),
                ]),
                'taskCount' => 0,
                'taskCountByState' => [],
                'tasksWithErrorsCount' => 0,
                'tasksWithWarningsCount' => 0,
                'skippedTaskCount' => 0,
                'cancelledTaskCount' => 0,
                'isPublic' => false,
                'errorCount' => 0,
                'warningCount' => 0,
                'owners' => [
                    ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                ],
                'rejectionReason' => null,
                'ammendments' => [],
                'crawlSummary' => null,
                'expectedSerializedData' => [
                    'id' => 1,
                    'user' => 'user@example.com',
                    'website' => 'http://example.com/',
                    'state' => 'completed',
                    'url_count' => 12,
                    'task_types' => [],
                    'task_type_options' => [],
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'parameters' => '',
                    'task_count' => 0,
                    'task_count_by_state' => [],
                    'errored_task_count' => 0,
                    'warninged_task_count' => 0,
                    'skipped_task_count' => 0,
                    'cancelled_task_count' => 0,
                    'is_public' => false,
                    'error_count' => 0,
                    'warning_count' => 0,
                    'owners' => [
                        'user@example.com',
                    ],
                ],
            ],
            'with task types, task type options, parameters, time period' => [
                'job' => ModelFactory::createJob([
                    ModelFactory::JOB_ID => 1,
                    ModelFactory::JOB_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                    ]),
                    ModelFactory::JOB_STATE => ModelFactory::createState('job-completed'),
                    ModelFactory::JOB_URL_COUNT => 12,
                    ModelFactory::JOB_REQUESTED_TASK_TYPES => [
                        $htmlValidationTaskType,
                        $cssValidationTaskType,
                    ],
                    ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [
                        ModelFactory::createTaskTypeOptions([
                            ModelFactory::TASK_TYPE_OPTIONS_TASK_TYPE => $htmlValidationTaskType,
                            ModelFactory::TASK_TYPE_OPTIONS_TASK_OPTIONS => [
                                'html-validation-foo' => 'html-validation-bar',
                            ],
                        ]),
                        ModelFactory::createTaskTypeOptions([
                            ModelFactory::TASK_TYPE_OPTIONS_TASK_TYPE => $cssValidationTaskType,
                            ModelFactory::TASK_TYPE_OPTIONS_TASK_OPTIONS => [
                                'css-validation-foo' => 'css-validation-bar',
                            ],
                        ]),
                    ],
                    ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                    ]),
                    ModelFactory::JOB_PARAMETERS => 'foo',
                    ModelFactory::JOB_TIME_PERIOD => ModelFactory::createTimePeriod([
                        ModelFactory::TIME_PERIOD_START_DATE_TIME => new \DateTime('2010-01-01 00:00:00'),
                        ModelFactory::TIME_PERIOD_END_DATE_TIME => new \DateTime('2011-01-01 00:00:00'),
                    ]),
                ]),
                'taskCount' => 9,
                'taskCountByState' => [
                    'task-completed' => 6,
                    'task-cancelled' => 1,
                    'task-skipped' => 1,
                    'task-failed' => 2,
                ],
                'tasksWithErrorsCount' => 0,
                'tasksWithWarningsCount' => 1,
                'skippedTaskCount' => 1,
                'cancelledTaskCount' => 1,
                'isPublic' => true,
                'errorCount' => 0,
                'warningCount' => 7,
                'owners' => [
                    ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user1@example.com',
                    ]),
                    ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user2@example.com',
                    ]),
                ],
                'rejectionReason' => null,
                'ammendments' => [],
                'crawlSummary' => null,
                'expectedSerializedData' => [
                    'id' => 1,
                    'user' => 'user@example.com',
                    'website' => 'http://example.com/',
                    'state' => 'completed',
                    'url_count' => 12,
                    'task_types' => [
                        [
                            'name' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        [
                            'name' => TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                    'task_type_options' => [
                        TaskTypeService::HTML_VALIDATION_TYPE => [
                            'html-validation-foo' => 'html-validation-bar',
                        ],
                        TaskTypeService::CSS_VALIDATION_TYPE => [
                            'css-validation-foo' => 'css-validation-bar',
                        ],
                    ],
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'parameters' => 'foo',
                    'time_period' => [
                        'start_date_time' => '2010-01-01T00:00:00+00:00',
                        'end_date_time' => '2011-01-01T00:00:00+00:00',
                    ],
                    'task_count' => 9,
                    'task_count_by_state' => [
                        'task-completed' => 6,
                        'task-cancelled' => 1,
                        'task-skipped' => 1,
                        'task-failed' => 2,
                    ],
                    'errored_task_count' => 0,
                    'warninged_task_count' => 1,
                    'skipped_task_count' => 1,
                    'cancelled_task_count' => 1,
                    'is_public' => true,
                    'error_count' => 0,
                    'warning_count' => 7,
                    'owners' => [
                        'user1@example.com',
                        'user2@example.com',
                    ],
                ],
            ],
            'rejection reason' => [
                'job' => ModelFactory::createJob([
                    ModelFactory::JOB_ID => 1,
                    ModelFactory::JOB_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                    ]),
                    ModelFactory::JOB_STATE => ModelFactory::createState('job-completed'),
                    ModelFactory::JOB_URL_COUNT => 12,
                    ModelFactory::JOB_REQUESTED_TASK_TYPES => [],
                    ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [],
                    ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                    ]),
                ]),
                'taskCount' => 0,
                'taskCountByState' => [],
                'tasksWithErrorsCount' => 0,
                'tasksWithWarningsCount' => 0,
                'skippedTaskCount' => 0,
                'cancelledTaskCount' => 0,
                'isPublic' => false,
                'errorCount' => 0,
                'warningCount' => 0,
                'owners' => [
                    ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                ],
                'rejectionReason' => ModelFactory::createRejectionReason([
                    ModelFactory::REJECTION_REASON_REASON => 'reason-name',
                    ModelFactory::REJECTION_REASON_CONSTRAINT => ModelFactory::createAccountPlanConstraint([
                        ModelFactory::CONSTRAINT_NAME => 'constraint-name',
                        ModelFactory::CONSTRAINT_LIMIT => 33,
                    ]),
                ]),
                'ammendments' => [],
                'crawlSummary' => null,
                'expectedSerializedData' => [
                    'id' => 1,
                    'user' => 'user@example.com',
                    'website' => 'http://example.com/',
                    'state' => 'completed',
                    'url_count' => 12,
                    'task_types' => [],
                    'task_type_options' => [],
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'parameters' => '',
                    'task_count' => 0,
                    'task_count_by_state' => [],
                    'errored_task_count' => 0,
                    'warninged_task_count' => 0,
                    'skipped_task_count' => 0,
                    'cancelled_task_count' => 0,
                    'is_public' => false,
                    'error_count' => 0,
                    'warning_count' => 0,
                    'owners' => [
                        'user@example.com',
                    ],
                    'rejection' => [
                        'reason' => 'reason-name',
                        'constraint' => [
                            'name' => 'constraint-name',
                            'limit' => 33,
                            'is_available' => true,
                        ],
                    ],
                ],
            ],
            'ammendments' => [
                'job' => ModelFactory::createJob([
                    ModelFactory::JOB_ID => 1,
                    ModelFactory::JOB_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                    ]),
                    ModelFactory::JOB_STATE => ModelFactory::createState('job-completed'),
                    ModelFactory::JOB_URL_COUNT => 12,
                    ModelFactory::JOB_REQUESTED_TASK_TYPES => [],
                    ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [],
                    ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                    ]),
                ]),
                'taskCount' => 0,
                'taskCountByState' => [],
                'tasksWithErrorsCount' => 0,
                'tasksWithWarningsCount' => 0,
                'skippedTaskCount' => 0,
                'cancelledTaskCount' => 0,
                'isPublic' => false,
                'errorCount' => 0,
                'warningCount' => 0,
                'owners' => [
                    ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                ],
                'rejectionReason' => null,
                'ammendments' => [
                    ModelFactory::createAmmendment([
                        ModelFactory::AMMENDMENT_REASON => 'ammendment-1-reason',
                        ModelFactory::AMMENDMENT_CONSTRAINT => ModelFactory::createAccountPlanConstraint([
                            ModelFactory::CONSTRAINT_NAME => 'constraint-1-name',
                            ModelFactory::CONSTRAINT_LIMIT => 10,
                        ]),
                    ]),
                    ModelFactory::createAmmendment([
                        ModelFactory::AMMENDMENT_REASON => 'ammendment-2-reason',
                        ModelFactory::AMMENDMENT_CONSTRAINT => ModelFactory::createAccountPlanConstraint([
                            ModelFactory::CONSTRAINT_NAME => 'constraint-2-name',
                            ModelFactory::CONSTRAINT_LIMIT => 20,
                        ]),
                    ]),
                ],
                'crawlSummary' => null,
                'expectedSerializedData' => [
                    'id' => 1,
                    'user' => 'user@example.com',
                    'website' => 'http://example.com/',
                    'state' => 'completed',
                    'url_count' => 12,
                    'task_types' => [],
                    'task_type_options' => [],
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'parameters' => '',
                    'task_count' => 0,
                    'task_count_by_state' => [],
                    'errored_task_count' => 0,
                    'warninged_task_count' => 0,
                    'skipped_task_count' => 0,
                    'cancelled_task_count' => 0,
                    'is_public' => false,
                    'error_count' => 0,
                    'warning_count' => 0,
                    'owners' => [
                        'user@example.com',
                    ],
                    'ammendments' => [
                        [
                            'reason' => 'ammendment-1-reason',
                            'constraint' => [
                                'name' => 'constraint-1-name',
                                'limit' => 10,
                                'is_available' => true,
                            ],
                        ],
                        [
                            'reason' => 'ammendment-2-reason',
                            'constraint' => [
                                'name' => 'constraint-2-name',
                                'limit' => 20,
                                'is_available' => true,
                            ],
                        ],
                    ],
                ],
            ],
            'crawl summary' => [
                'job' => ModelFactory::createJob([
                    ModelFactory::JOB_ID => 1,
                    ModelFactory::JOB_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                    ]),
                    ModelFactory::JOB_STATE => ModelFactory::createState('job-completed'),
                    ModelFactory::JOB_URL_COUNT => 12,
                    ModelFactory::JOB_REQUESTED_TASK_TYPES => [],
                    ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [],
                    ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                    ]),
                ]),
                'taskCount' => 0,
                'taskCountByState' => [],
                'tasksWithErrorsCount' => 0,
                'tasksWithWarningsCount' => 0,
                'skippedTaskCount' => 0,
                'cancelledTaskCount' => 0,
                'isPublic' => false,
                'errorCount' => 0,
                'warningCount' => 0,
                'owners' => [
                    ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                ],
                'rejectionReason' => null,
                'ammendments' => [],
                'crawlSummary' => new CrawlSummary(
                    ModelFactory::createJob([
                        ModelFactory::JOB_ID => 2,
                        ModelFactory::JOB_USER => ModelFactory::createUser([
                            ModelFactory::USER_EMAIL => 'user@example.com',
                        ]),
                        ModelFactory::JOB_WEBSITE => ModelFactory::createWebsite([
                            ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                        ]),
                        ModelFactory::JOB_STATE => ModelFactory::createState('job-in-progress'),
                        ModelFactory::JOB_URL_COUNT => 12,
                        ModelFactory::JOB_REQUESTED_TASK_TYPES => [],
                        ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [],
                        ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                            ModelFactory::JOB_TYPE_NAME => JobTypeService::CRAWL_NAME,
                        ]),
                    ]),
                    10,
                    20,
                    30
                ),
                'expectedSerializedData' => [
                    'id' => 1,
                    'user' => 'user@example.com',
                    'website' => 'http://example.com/',
                    'state' => 'completed',
                    'url_count' => 12,
                    'task_types' => [],
                    'task_type_options' => [],
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'parameters' => '',
                    'task_count' => 0,
                    'task_count_by_state' => [],
                    'errored_task_count' => 0,
                    'warninged_task_count' => 0,
                    'skipped_task_count' => 0,
                    'cancelled_task_count' => 0,
                    'is_public' => false,
                    'error_count' => 0,
                    'warning_count' => 0,
                    'owners' => [
                        'user@example.com',
                    ],
                    'crawl' => [
                        'id' => 2,
                        'state' => 'in-progress',
                        'processed_url_count' => 10,
                        'discovered_url_count' => 20,
                        'limit' => 30,
                    ],
                ],
            ],
        ];
    }
}
