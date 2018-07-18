<?php

namespace App\Tests\Factory;

use App\Services\Request\Factory\Task\CompleteRequestFactory;
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
