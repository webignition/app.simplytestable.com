<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Entity\Task;

use ReflectionClass;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Tests\Factory\ModelFactory;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param int $id
     * @param string $url
     * @param State $state
     * @param Worker|null $worker
     * @param Type $type
     * @param TimePeriod|null $timePeriod
     * @param int|null $remoteId
     * @param Output|null $output
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(
        $id,
        $url,
        State $state,
        $worker,
        $type,
        $timePeriod,
        $remoteId,
        $output,
        $expectedReturnValue
    ) {
        $task = new Task();

        $this->setTaskId($task, $id);
        $task->setUrl($url);
        $task->setState($state);
        $task->setWorker($worker);
        $task->setType($type);
        $task->setTimePeriod($timePeriod);
        $task->setRemoteId($remoteId);
        $task->setOutput($output);

        $this->assertEquals($expectedReturnValue, $task->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'empty worker' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'worker' => null,
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => null,
                'remoteId' =>  null,
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'worker' => '',
                    'type' => 'HTML validation'
                ],
            ],
            'non-empty worker' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'worker' => ModelFactory::createWorker(
                    'worker.example.com',
                    ModelFactory::createState('foo'),
                    'token'
                ),
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => null,
                'remoteId' =>  null,
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'worker' => 'worker.example.com',
                    'type' => 'HTML validation'
                ],
            ],
            'time period present, all values empty' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'worker' => null,
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => ModelFactory::createTimePeriod([
                    ModelFactory::TIME_PERIOD_START_DATE_TIME => null,
                    ModelFactory::TIME_PERIOD_END_DATE_TIME => null,
                ]),
                'remoteId' =>  null,
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'worker' => '',
                    'type' => 'HTML validation'
                ],
            ],
            'time period present, end date time empty values empty' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'worker' => null,
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => ModelFactory::createTimePeriod([
                    ModelFactory::TIME_PERIOD_START_DATE_TIME => new \DateTime('2017-10-24 19:14:00'),
                    ModelFactory::TIME_PERIOD_END_DATE_TIME => null,
                ]),
                'remoteId' =>  null,
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'worker' => '',
                    'type' => 'HTML validation',
                    'time_period' => [
                        'start_date_time' => 1508872440,
                    ],
                ],
            ],
            'time period present' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'worker' => null,
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => ModelFactory::createTimePeriod([
                    ModelFactory::TIME_PERIOD_START_DATE_TIME => new \DateTime('2017-10-24 19:14:00'),
                    ModelFactory::TIME_PERIOD_END_DATE_TIME => new \DateTime('2017-10-24 20:14:00'),
                ]),
                'remoteId' =>  null,
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'worker' => '',
                    'type' => 'HTML validation',
                    'time_period' => [
                        'start_date_time' => 1508872440,
                        'end_date_time' => 1508876040,
                    ],
                ],
            ],
            'non-empty remote id' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'worker' => null,
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => null,
                'remoteId' => 2,
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'worker' => '',
                    'type' => 'HTML validation',
                    'remote_id' => 2,
                ],
            ],
            'with output' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'worker' => null,
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => null,
                'remoteId' => null,
                'output' => ModelFactory::createTaskOutput([
                    ModelFactory::TASK_OUTPUT_OUTPUT => '"output content"',
                    ModelFactory::TASK_OUTPUT_CONTENT_TYPE => 'application/json',
                    ModelFactory::TASK_OUTPUT_ERROR_COUNT => 3,
                    ModelFactory::TASK_OUTPUT_WARNING_COUNT => 4,
                ]),
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'worker' => '',
                    'type' => 'HTML validation',
                    'output' => [
                        'output' => '"output content"',
                        'content_type' => 'application/json',
                        'error_count' => 3,
                        'warning_count' => 4,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param Task $task
     * @param int $id
     */
    private function setTaskId(Task $task, $id)
    {
        $reflectionClass = new ReflectionClass(Task::class);

        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($task, $id);
    }
}
