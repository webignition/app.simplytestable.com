<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\CancelAction;

use SimplyTestable\ApiBundle\Services\JobService;

abstract class IsCancelledTest extends CancelTest {

    protected function getExpectedJobEndingState() {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobCancelledState = $stateService->fetch(JobService::CANCELLED_STATE);

        return $jobCancelledState;
    }

    protected function getExpectedResponseCode() {
        return 200;
    }

}


