<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteAction\UrlDiscovery;


class TaskErrorIsIgnoredTest extends CompleteDiscoveryTest {

    protected function performControllerAction() {
        $task = $this->crawlJob->getTasks()->first();
        $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '{"messages":[{"message":"Unauthorized","messageId":"http-retrieval-401","type":"error"}]}',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
    }

}


