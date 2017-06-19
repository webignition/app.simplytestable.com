<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\TaskIdsAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use Symfony\Component\HttpFoundation\Request;

class TaskIdsTest extends BaseControllerJsonTestCase
{
    protected function getActionName()
    {
        return 'taskIdsAction';
    }

    public function testTaskIdsAction()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->createJobFactory()->createResolveAndPrepare();
        $jobStatus = $this->fetchJobStatusObject($job);

        $jobController = $this->createControllerFactory()->createJobController(new Request());
        $response = $jobController->taskIdsAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
        $taskIds = json_decode($response->getContent());

        $expectedTaskIdCount = $jobStatus->url_count * count($jobStatus->task_types);

        $this->assertEquals($expectedTaskIdCount, count($taskIds));

        foreach ($taskIds as $taskId) {
            $this->assertInternalType('integer', $taskId);
            $this->assertGreaterThan(0, $taskId);
        }
    }
}
