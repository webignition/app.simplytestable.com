<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction;

class NoEmailNoPasswordTest extends FailureTest {

    protected function getRequestPostData() {
        return [];
    }
    
}

