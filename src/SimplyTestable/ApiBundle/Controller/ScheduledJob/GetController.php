<?php

namespace SimplyTestable\ApiBundle\Controller\ScheduledJob;

class GetController extends ScheduledJobController {

    public function getAction($id) {
        return $this->sendResponse([]);
    }

}
