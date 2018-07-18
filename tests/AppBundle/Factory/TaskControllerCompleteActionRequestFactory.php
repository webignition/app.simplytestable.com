<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use Symfony\Component\HttpFoundation\Request;

class TaskControllerCompleteActionRequestFactory
{
    public static function create($postData, $routeParams)
    {
        return new Request([], $postData, [
            CompleteRequestFactory::ATTRIBUTE_ROUTE_PARAMS => $routeParams
        ]);
    }
}
