<?php

namespace App\Tests\Unit\Request\Task;

use App\Entity\State;
use App\Entity\Task\Task;
use App\Request\Task\CompleteRequest;
use webignition\InternetMediaType\InternetMediaType;

class CompleteRequestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param \DateTime $endDateTime
     * @param string $output
     * @param InternetMediaType $contentType
     * @param State $state
     * @param int $errorCount
     * @param int $warningCount
     * @param Task[] $tasks
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
        $endDateTime,
        $output,
        $contentType,
        $state,
        $errorCount,
        $warningCount,
        $tasks,
        $expectedIsValid,
        $expectedEndDateTime,
        $expectedOutput,
        $expectedContentType,
        $expectedState,
        $expectedErrorCount,
        $expectedWarningCount,
        $expectedTasks
    ) {
        $completeRequest = new CompleteRequest(
            $endDateTime,
            $output,
            $contentType,
            $state,
            $errorCount,
            $warningCount,
            $tasks
        );

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
        $applicationJsonContentType = new InternetMediaType('application', 'json');
        $completedState = State::create(Task::STATE_COMPLETED);
        $now = new \DateTime();

        return [
            'endDateTime missing is invalid' => [
                'endDateTime' => null,
                'output' => '',
                'contentType' => $applicationJsonContentType,
                'state' => $completedState,
                'errorCount' => 1,
                'warningCount' => 2,
                'tasks' => [],
                'expectedIsValid' => false,
                'expectedEndDateTime' => null,
                'expectedOutput' => '',
                'expectedContentType' => $applicationJsonContentType,
                'expectedState' => $completedState,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 2,
                'expectedTasks' => [],
            ],
            'contentType missing is invalid' => [
                'endDateTime' => $now,
                'output' => '',
                'contentType' => null,
                'state' => $completedState,
                'errorCount' => 1,
                'warningCount' => 2,
                'tasks' => [],
                'expectedIsValid' => false,
                'expectedEndDateTime' => $now,
                'expectedOutput' => '',
                'expectedContentType' => null,
                'expectedState' => $completedState,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 2,
                'expectedTasks' => [],
            ],
            'state missing is invalid' => [
                'endDateTime' => $now,
                'output' => '',
                'contentType' => $applicationJsonContentType,
                'state' => null,
                'errorCount' => 1,
                'warningCount' => 2,
                'tasks' => [],
                'expectedIsValid' => false,
                'expectedEndDateTime' => $now,
                'expectedOutput' => '',
                'expectedContentType' => $applicationJsonContentType,
                'expectedState' => null,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 2,
                'expectedTasks' => [],
            ],
            'tasks missing is valid' => [
                'endDateTime' => $now,
                'output' => '',
                'contentType' => $applicationJsonContentType,
                'state' => $completedState,
                'errorCount' => 1,
                'warningCount' => 2,
                'tasks' => null,
                'expectedIsValid' => true,
                'expectedEndDateTime' => $now,
                'expectedOutput' => '',
                'expectedContentType' => $applicationJsonContentType,
                'expectedState' => $completedState,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 2,
                'expectedTasks' => null,
            ],
            'valid' => [
                'endDateTime' => $now,
                'output' => 'foo',
                'contentType' => $applicationJsonContentType,
                'state' => $completedState,
                'errorCount' => 1,
                'warningCount' => 2,
                'tasks' => [],
                'expectedIsValid' => true,
                'expectedEndDateTime' => $now,
                'expectedOutput' => 'foo',
                'expectedContentType' => $applicationJsonContentType,
                'expectedState' => $completedState,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 2,
                'expectedTasks' => [],
            ],
        ];
    }
}
