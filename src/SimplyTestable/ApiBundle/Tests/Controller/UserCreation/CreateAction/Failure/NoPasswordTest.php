<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction\Failure;

class NoPasswordTest extends FailureTest {

    protected function getRequestPostData() {
        return [
            'email' => 'user@example.com'
        ];
    }
    
}

