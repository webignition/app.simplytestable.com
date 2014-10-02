<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction;

use SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\ActionTest;

abstract class RequestTest extends ActionTest {

    /**
     * @return array
     */
    protected function getRequestPostData() {
        return [
            'worker_hostname' => 'worker.example.com',
            'worker_token' => 'foo'
        ];
    }
    
}