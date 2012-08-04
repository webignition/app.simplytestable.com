<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use Symfony\Component\HttpFoundation\Response;
//use SimplyTestable\ApiBundle\Entity\Job\Job;
//use SimplyTestable\ApiBundle\Entity\WebSite;
//use SimplyTestable\ApiBundle\Entity\State;

class WorkerController extends Controller
{    
    
    public function activateAction()
    {        
        var_dump("cp01");
        exit();
//        $this->siteRootUrl = $site_root_url;         
//        
//        /* @var $jobService \SimplyTestable\ApiBundle\Services\JobService */
//        $jobService = $this->get('simplytestable.services.jobservice');
//        $job = $jobService->create(
//            $this->getUser(),
//            $this->getWebsite(),
//            $this->getTaskTypes()
//        );
//     
//        $output = $this->container->get('serializer')->serialize($job, 'json');   
//        $formatter = new \webignition\JsonPrettyPrinter\JsonPrettyPrinter(); 
//        
//        return new Response($formatter->format($output));
    }    

}
