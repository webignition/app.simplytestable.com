<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction;

class NoEmailTest extends FailureTest {

    protected function getRequestPostData() {
        return [
            'password' => 'password'
        ];
    }

}

