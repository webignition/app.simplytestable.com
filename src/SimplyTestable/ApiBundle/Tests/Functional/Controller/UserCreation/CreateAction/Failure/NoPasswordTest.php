<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\CreateAction\Failure;

class NoPasswordTest extends FailureTest {

    protected function getRequestPostData() {
        return [
            'email' => 'user@example.com'
        ];
    }

}

