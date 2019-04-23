<?php

namespace App\Tests\Unit\Entity\Job;

use App\Entity\Job\Job;
use App\Entity\State;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Tests\Factory\ModelFactory;

class JobTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param Job $job
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(Job $job, $expectedReturnValue)
    {
        $this->assertEquals($expectedReturnValue, $job->jsonSerialize());
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
                    ModelFactory::JOB_STATE => State::create('job-completed'),
                    ModelFactory::JOB_URL_COUNT => 12,
                    ModelFactory::JOB_REQUESTED_TASK_TYPES => [],
                    ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [],
                    ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                    ]),
                ]),
                'expectedReturnValue' => [
                    'id' => 1,
                    'user' => 'user@example.com',
                    'website' => 'http://example.com/',
                    'state' => 'completed',
                    'url_count' => 12,
                    'task_types' => [],
                    'task_type_options' => [],
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'parameters' => '',
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
                    ModelFactory::JOB_STATE => State::create('job-completed'),
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
                'expectedReturnValue' => [
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
                ],
            ],
        ];
    }
}
