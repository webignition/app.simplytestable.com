<?php

namespace SimplyTestable\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class BeforeListener
{

    public function onKernelController(FilterControllerEvent $event)
    {
//        /*
//         * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
//         * If it is a class, it comes in array format
//         */
//        if (!is_array($controller)) {
//            return;
//        }
//
//        if($controller[0] instanceof TokenAuthenticatedController) {
//            $token = $event->getRequest()->get('token');
//            if (!in_array($token, $this->tokens)) {
//                throw new AccessDeniedHttpException('This action needs a valid token!');
//            }
//        }
    }
}