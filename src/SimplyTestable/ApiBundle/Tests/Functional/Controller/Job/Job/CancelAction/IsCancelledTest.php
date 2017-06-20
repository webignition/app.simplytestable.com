<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\CancelAction;

abstract class IsCancelledTest extends CancelTest {

    protected function getExpectedJobEndingState() {
        return $this->getJobService()->getCancelledState();
    }

    protected function getExpectedResponseCode() {
        return 200;
    }

}


