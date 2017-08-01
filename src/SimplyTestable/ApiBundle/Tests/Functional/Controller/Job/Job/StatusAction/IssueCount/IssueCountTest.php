<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\StatusAction\IssueCount;

use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

abstract class IssueCountTest extends BaseControllerJsonTestCase
{
    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var \stdClass
     */
    protected $jobData;

    protected function setUp()
    {
        parent::setUp();

        $jobFactory = new JobFactory($this->container);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $jobFactory->createResolveAndPrepare();
        foreach ($job->getTasks() as $task) {
            $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
                'end_date_time' => '2012-03-08 17:03:00',
                'output' => '[]',
                'contentType' => 'application/json',
                'state' => 'completed',
                'errorCount' => $this->getReportedErrorCount(),
                'warningCount' => $this->getReportedWarningCount()
            ], [
                CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
                CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
                CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
            ]);

            $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
            $taskController->completeAction();
        }

        $response = $this->getJobController('statusAction')->statusAction(self::CANONICAL_URL, $job->getId());
        $this->jobData = json_decode($response->getContent());
    }

    abstract protected function getReportedErrorCount();
    abstract protected function getReportedWarningCount();

    public function testErrorCount()
    {
        $this->assertEquals(
            $this->jobData->task_count * $this->getReportedErrorCount(),
            $this->jobData->error_count
        );
    }

    public function testWarningCount()
    {
        $this->assertEquals(
            $this->jobData->task_count * $this->getReportedWarningCount(),
            $this->jobData->warning_count
        );
    }
}
