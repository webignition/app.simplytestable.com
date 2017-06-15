<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount;

use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;

abstract class IssueCountTest extends BaseControllerJsonTestCase
{
    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var \stdClass
     */
    protected $jobData;

    public function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
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

            $this->createTaskController($taskCompleteRequest)->completeAction();
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
