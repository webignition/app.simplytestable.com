<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\ActivateAction\Failure;

class InvalidTokenTest extends FailureTest {

    protected function getConfirmationToken() {
        return 'foo';
    }
    
}

