<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction\MissingQueryData;

class LackingWorkerHostnameTest extends MissingQueryDataTest {

    /**
     * @return array
     */
    protected function getRequestPostData() {
        return [
            //'worker_hostname' => 'worker.example.com',
            'worker_token' => 'foo'
        ];
    }
    
}