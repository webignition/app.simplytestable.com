<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\CreateAction\Failure;

class NoEmailTest extends FailureTest {

    protected function getRequestPostData() {
        return [
            'password' => 'password'
        ];
    }

}
