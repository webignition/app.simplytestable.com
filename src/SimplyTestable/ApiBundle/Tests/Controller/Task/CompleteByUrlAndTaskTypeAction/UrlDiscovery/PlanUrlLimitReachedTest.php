<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction\UrlDiscovery;

class PlanUrlLimitReachedTest extends CompleteDiscoveryTest {

    protected function performControllerAction() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->crawlJob->getUser());
        $limit = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit();

        $task = $this->crawlJob->getTasks()->first();
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $limit)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
    }

}


