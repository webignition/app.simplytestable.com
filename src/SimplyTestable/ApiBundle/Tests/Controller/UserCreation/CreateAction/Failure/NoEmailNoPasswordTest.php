<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction\Failure;

class NoEmailNoPasswordTest extends FailureTest {

    protected function getRequestPostData() {
        return [];
    }
    
}

