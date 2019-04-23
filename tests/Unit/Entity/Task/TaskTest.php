<?php

namespace App\Tests\Unit\Entity\Task;

use ReflectionClass;
use App\Entity\State;
use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Entity\Task\Type;
use App\Entity\TimePeriod;
use App\Tests\Factory\ModelFactory;

class TaskTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param int $id
     * @param string $url
     * @param State $state
     * @param Type $type
     * @param TimePeriod|null $timePeriod
     * @param Output|null $output
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(
        $id,
        $url,
        State $state,
        $type,
        $timePeriod,
        $output,
        $expectedReturnValue
    ) {
        $task = new Task();

        $this->setTaskId($task, $id);
        $task->setUrl($url);
        $task->setState($state);
        $task->setType($type);
        $task->setTimePeriod($timePeriod);
        $task->setOutput($output);

        $this->assertEquals($expectedReturnValue, $task->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'default' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => null,
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'type' => 'HTML validation'
                ],
            ],
            'time period present, all values empty' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => ModelFactory::createTimePeriod([
                    ModelFactory::TIME_PERIOD_START_DATE_TIME => null,
                    ModelFactory::TIME_PERIOD_END_DATE_TIME => null,
                ]),
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'type' => 'HTML validation'
                ],
            ],
            'time period present, end date time empty values empty' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => ModelFactory::createTimePeriod([
                    ModelFactory::TIME_PERIOD_START_DATE_TIME => new \DateTime('2017-10-24 19:14:00'),
                    ModelFactory::TIME_PERIOD_END_DATE_TIME => null,
                ]),
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'type' => 'HTML validation',
                    'time_period' => [
                        'start_date_time' => '2017-10-24T19:14:00+00:00',
                    ],
                ],
            ],
            'time period present' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => ModelFactory::createTimePeriod([
                    ModelFactory::TIME_PERIOD_START_DATE_TIME => new \DateTime('2017-10-24 19:14:00'),
                    ModelFactory::TIME_PERIOD_END_DATE_TIME => new \DateTime('2017-10-24 20:14:00'),
                ]),
                'output' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'url' => 'http://foo.example.com',
                    'state' => 'foo-state',
                    'type' => 'HTML validation',
                    'time_period' => [
                        'start_date_time' => '2017-10-24T19:14:00+00:00',
                        'end_date_time' => '2017-10-24T20:14:00+00:00',
                    ],
                ],
            ],
            'with output' => [
                'id' => 1,
                'url' => 'http://foo.example.com',
                'state' => ModelFactory::createState('task-foo-state'),
                'type' => ModelFactory::createTaskType([
                    ModelFactory::TASK_TYPE_NAME => 'HTML validation',
                ]),
                'timePeriod' => null,
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
