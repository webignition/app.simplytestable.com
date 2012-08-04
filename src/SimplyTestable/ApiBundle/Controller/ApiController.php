<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

abstract class ApiController extends Controller
{      
    
    /**
     *
     * @param mixed $object
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    protected function sendResponse($object) {        
        $output = $this->container->get('serializer')->serialize($object, 'json');   
        $formatter = new \webignition\JsonPrettyPrinter\JsonPrettyPrinter(); 
        
        return new Response($formatter->format($output)); 
    }
}
