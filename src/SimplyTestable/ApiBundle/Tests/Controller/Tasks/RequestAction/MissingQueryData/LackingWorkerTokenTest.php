<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Tasks\RequestAction\MissingQueryData;

class LackingWorkerTokenTest extends MissingQueryDataTest {

    /**
     * @return array
     */
    protected function getRequestPostData() {
        return [
            'worker_hostname' => 'worker.example.com',
            //'worker_token' => 'foo'
        ];
    }
    
}