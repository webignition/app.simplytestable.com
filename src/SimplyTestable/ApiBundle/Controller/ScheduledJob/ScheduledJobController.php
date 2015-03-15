<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ApiController;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;

class ScheduledJobController extends ApiController {

    /**
     * @return ScheduledJobService
     */
    protected function getScheduledJobService() {
        return $this->container->get('simplytestable.services.scheduledjob.service');
    }

}
