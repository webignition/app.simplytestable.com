<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Entity\Job;

use ReflectionClass;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\ModelFactory;

class JobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param int $id
     * @param User $user
     * @param WebSite $website
     * @param State $state
     * @param int $urlCount
     * @param TaskType[] $requestedTaskTypes
     * @param array $taskTypeOptionsCollection
     * @param JobType $type
     * @param string $parameters
     * @param TimePeriod|null $timePeriod
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(
        $id,
        User $user,
        WebSite $website,
        State $state,
        $urlCount,
        $requestedTaskTypes,
        $taskTypeOptionsCollection,
        JobType $type,
        $parameters,
        $timePeriod,
        $expectedReturnValue
    ) {
        $job = new Job();

        $this->setJobId($job, $id);

        $job->setUser($user);
        $job->setWebsite($website);
        $job->setState($state);
        $job->setUrlCount($urlCount);
        $job->setTimePeriod($timePeriod);

        foreach ($requestedTaskTypes as $requestedTaskType) {
            $job->addRequestedTaskType($requestedTaskType);
        }

        foreach ($taskTypeOptionsCollection as $taskTypeOptions) {
            $job->addTaskTypeOption($taskTypeOptions);
        }

        $job->setType($type);
        $job->setParameters($parameters);

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
                'id' => 1,
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'website' => ModelFactory::createWebsite([
                    ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                ]),
                'state' => ModelFactory::createState('job-completed'),
                'urlCount' => 12,
                'requestedTaskTypes' => [],
                'taskTypeOptionsCollection' => [],
                'type' => ModelFactory::createJobType([
                    ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                ]),
                'parameters' => '',
                'timePeriod' => null,
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
                'id' => 1,
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'website' => ModelFactory::createWebsite([
                    ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                ]),
                'state' => ModelFactory::createState('job-completed'),
                'urlCount' => 12,
                'requestedTaskTypes' => [
                    $htmlValidationTaskType,
                    $cssValidationTaskType,
                ],
                'taskTypeOptionsCollection' => [
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
                'type' => ModelFactory::createJobType([
                    ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
                ]),
                'parameters' => 'foo',
                'timePeriod' => ModelFactory::createTimePeriod([
                    ModelFactory::TIME_PERIOD_START_DATE_TIME => new \DateTime('2010-01-01 00:00:00'),
                    ModelFactory::TIME_PERIOD_END_DATE_TIME => new \DateTime('2011-01-01 00:00:00'),
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
                        'start_date_time' => 1262304000,
                        'end_date_time' => 1293840000,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Job $job
     * @param int $id
     */
    private function setJobId(Job $job, $id)
    {
        $reflectionClass = new ReflectionClass(Job::class);

        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($job, $id);
    }
}
