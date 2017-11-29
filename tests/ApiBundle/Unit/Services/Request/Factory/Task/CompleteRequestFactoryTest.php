<?php

namespace Tests\ApiBundle\Unit\Services\Request\Factory\Task;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\InternetMediaTypeFactory;
use Tests\ApiBundle\Factory\StateFactory;
use Tests\ApiBundle\Factory\TaskTypeFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use webignition\InternetMediaType\InternetMediaType;

class CompleteRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider createDataProvider
     * @param Request $request
     * @param StateService $stateService
     * @param TaskTypeService $taskTypeService
     * @param TaskService $taskService
     * @param bool $expectedIsValid
     * @param \DateTime $expectedEndDateTime
     * @param string $expectedOutput
     * @param InternetMediaType $expectedContentType
     * @param State $expectedState
     * @param int $expectedErrorCount
     * @param int $expectedWarningCount
     * @param Task[] $expectedTasks
     */
    public function testCreate(
        Request $request,
        StateService $stateService,
        TaskTypeService $taskTypeService,
        TaskService $taskService,
        $expectedIsValid,
        $expectedEndDateTime,
        $expectedOutput,
        $expectedContentType,
        $expectedState,
        $expectedErrorCount,
        $expectedWarningCount,
        $expectedTasks
    ) {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $completeRequestFactory = new CompleteRequestFactory(
            $requestStack,
            $stateService,
            $taskTypeService,
            $taskService
        );
        $completeRequest = $completeRequestFactory->create();

        $this->assertEquals($expectedIsValid, $completeRequest->isValid());
        $this->assertEquals($expectedEndDateTime, $completeRequest->getEndDateTime());
        $this->assertEquals($expectedOutput, $completeRequest->getOutput());
        $this->assertEquals($expectedContentType, $completeRequest->getContentType());
        $this->assertEquals($expectedState, $completeRequest->getState());
        $this->assertEquals($expectedErrorCount, $completeRequest->getErrorCount());
        $this->assertEquals($expectedWarningCount, $completeRequest->getWarningCount());
        $this->assertEquals($expectedTasks, $completeRequest->getTasks());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $completedState = StateFactory::create(Task::STATE_COMPLETED);
        $htmlValidationTaskType = TaskTypeFactory::create('html validation');
        $applicationJsonContentType = InternetMediaTypeFactory::create('application', 'json');

        $endDateTime = new \DateTime('2017-11-23T14:02:01+00:00');

        $tasks = [
            $this->createTask(),
            $this->createTask(),
        ];

        return [
            'empty post data, invalid' => [
                'request' => new Request([], [], [
                    CompleteRequestFactory::ATTRIBUTE_ROUTE_PARAMS => [
                        CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                        CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'f4aa3479641e8bb1e2744857a3b687a5',
                    ],
                ]),
                'stateService' => $this->createStateService($completedState),
                'taskTypeService' => $this->createTaskTypeService(
                    $htmlValidationTaskType->getName(),
                    $htmlValidationTaskType
                ),
                'taskService' => $this->createTaskService([], [
                    'canonicalUrl' => 'http://example.com/',
                    'taskType' => $htmlValidationTaskType,
                    'parameterHash' => 'f4aa3479641e8bb1e2744857a3b687a5',
                ]),
                'expectedIsValid' => false,
                'expectedEndDateTime' => null,
                'expectedOutput' => '',
                'expectedContentType' => null,
                'expectedState' => $completedState,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedTasks' => null,
            ],
            'no tasks, valid' => [
                'request' => new Request([], [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $endDateTime->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                ], [
                    CompleteRequestFactory::ATTRIBUTE_ROUTE_PARAMS => [
                        CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                        CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'f4aa3479641e8bb1e2744857a3b687a5',
                    ],
                ]),
                'stateService' => $this->createStateService($completedState),
                'taskTypeService' => $this->createTaskTypeService(
                    $htmlValidationTaskType->getName(),
                    $htmlValidationTaskType
                ),
                'taskService' => $this->createTaskService([], [
                    'canonicalUrl' => 'http://example.com/',
                    'taskType' => $htmlValidationTaskType,
                    'parameterHash' => 'f4aa3479641e8bb1e2744857a3b687a5',
                ]),
                'expectedIsValid' => true,
                'expectedEndDateTime' => $endDateTime,
                'expectedOutput' => '',
                'expectedContentType' => $applicationJsonContentType,
                'expectedState' => $completedState,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedTasks' => null,
            ],
            'invalid task type, valid' => [
                'request' => new Request([], [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $endDateTime->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                ], [
                    CompleteRequestFactory::ATTRIBUTE_ROUTE_PARAMS => [
                        CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => 'foo',
                        CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'f4aa3479641e8bb1e2744857a3b687a5',
                    ],
                ]),
                'stateService' => $this->createStateService($completedState),
                'taskTypeService' => $this->createTaskTypeService(
                    'foo',
                    null
                ),
                'taskService' => $this->createTaskService([], [
                    'canonicalUrl' => 'http://example.com/',
                    'taskType' => $htmlValidationTaskType,
                    'parameterHash' => 'f4aa3479641e8bb1e2744857a3b687a5',
                ]),
                'expectedIsValid' => true,
                'expectedEndDateTime' => $endDateTime,
                'expectedOutput' => '',
                'expectedContentType' => $applicationJsonContentType,
                'expectedState' => $completedState,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedTasks' => null,
            ],
            'valid' => [
                'request' => new Request([], [
                    CompleteRequestFactory::PARAMETER_END_DATE_TIME => $endDateTime->format('c'),
                    CompleteRequestFactory::PARAMETER_CONTENT_TYPE => (string)$applicationJsonContentType,
                ], [
                    CompleteRequestFactory::ATTRIBUTE_ROUTE_PARAMS => [
                        CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $htmlValidationTaskType->getName(),
                        CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => 'f4aa3479641e8bb1e2744857a3b687a5',
                    ],
                ]),
                'stateService' => $this->createStateService($completedState),
                'taskTypeService' => $this->createTaskTypeService(
                    $htmlValidationTaskType->getName(),
                    $htmlValidationTaskType
                ),
                'taskService' => $this->createTaskService($tasks, [
                    'canonicalUrl' => 'http://example.com/',
                    'taskType' => $htmlValidationTaskType,
                    'parameterHash' => 'f4aa3479641e8bb1e2744857a3b687a5',
                ]),
                'expectedIsValid' => true,
                'expectedEndDateTime' => $endDateTime,
                'expectedOutput' => '',
                'expectedContentType' => $applicationJsonContentType,
                'expectedState' => $completedState,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedTasks' => $tasks,
            ],
        ];
    }

    /**
     * @param Task[] $equivalentTasks
     * @param array $getEquivalentTasksArgs
     *
     * @return Mock|TaskService
     */
    private function createTaskService($equivalentTasks, $getEquivalentTasksArgs)
    {
        $incompleteStates = [
            StateFactory::create(Task::STATE_IN_PROGRESS),
            StateFactory::create(Task::STATE_QUEUED),
            StateFactory::create(Task::STATE_QUEUED_FOR_ASSIGNMENT),
        ];

        /* @var Mock|TaskService $taskService */
        $taskService = \Mockery::mock(TaskService::class);
        $taskService
            ->shouldReceive('getIncompleteStateNames')
            ->andReturn([
                Task::STATE_IN_PROGRESS,
                Task::STATE_QUEUED,
                Task::STATE_QUEUED_FOR_ASSIGNMENT,
            ]);

        $taskService
            ->shouldReceive('getEquivalentTasks')

            ->with(
                $getEquivalentTasksArgs['canonicalUrl'],
                $getEquivalentTasksArgs['taskType'],
                $getEquivalentTasksArgs['parameterHash'],
                $incompleteStates
            )->andReturn($equivalentTasks);

        return $taskService;
    }
    /**
     * @param State $stateToFetch
     *
     * @return Mock|StateService
     */
    private function createStateService(State $stateToFetch)
    {
        /* @var Mock|StateService $stateService */
        $stateService = \Mockery::mock(StateService::class);
        $stateService
            ->shouldReceive('get')
            ->andReturn($stateToFetch);

        $stateService
            ->shouldReceive('getCollection')
            ->with([
                Task::STATE_IN_PROGRESS,
                Task::STATE_QUEUED,
                Task::STATE_QUEUED_FOR_ASSIGNMENT,
            ])
            ->andReturn([
                StateFactory::create(Task::STATE_IN_PROGRESS),
                StateFactory::create(Task::STATE_QUEUED),
                StateFactory::create(Task::STATE_QUEUED_FOR_ASSIGNMENT),
            ]);

        return $stateService;
    }

    /**
     * @param string $name
     * @param TaskType $taskType
     *
     * @return Mock|TaskTypeService
     */
    private function createTaskTypeService($name, $taskType)
    {
        /* @var Mock|TaskTypeService $taskTypeService */
        $taskTypeService = \Mockery::mock(TaskTypeService::class);
        $taskTypeService
            ->shouldReceive('get')
            ->with($name)
            ->andReturn($taskType);

        return $taskTypeService;
    }

    /**
     * @return Task
     */
    private function createTask()
    {
        $task = new Task();

        return $task;
    }
}
