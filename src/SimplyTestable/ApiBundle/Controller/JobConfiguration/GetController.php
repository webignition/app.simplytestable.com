<?php

namespace SimplyTestable\ApiBundle\Controller\JobConfiguration;

use SimplyTestable\ApiBundle\Controller\ApiController;
use Symfony\Component\HttpFoundation\Response;

class GetController extends ApiController {

    public function getAction() {
        return new Response('', 200);
    }

}
