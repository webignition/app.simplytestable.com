<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction\Failure;

class InvalidTokenTest extends FailureTest {

    protected function getConfirmationToken() {
        return 'foo';
    }

}

