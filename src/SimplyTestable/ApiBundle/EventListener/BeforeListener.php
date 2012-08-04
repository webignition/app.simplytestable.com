<?php

namespace SimplyTestable\ApiBundle\EventListener;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Console\Input\InputArgument;
use SimplyTestable\ApiBundle\Controller\ApiController;

class BeforeListener
{

    public function onKernelController(FilterControllerEvent $event)
    {     
        $controllerCallable = $event->getController();
        
        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controllerCallable)) {
            return;
        }
        
        $controller = $controllerCallable[0];
        $methodName = $controllerCallable[1];
        
        if ($controller instanceof ApiController) {
            /* @var $controller ApiController */            
            $methodArguments = $controller->getArguments($methodName);
            foreach ($controller->getInputDefinition($methodName)->getArguments() as $inputArgument) {
                /* @var $inputArgument InputArgument */
                if ($inputArgument->isRequired()) {
                    if (!$methodArguments->has($inputArgument->getName())) {
                        throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
                    }
                }
            }           
        }
        

    }

}