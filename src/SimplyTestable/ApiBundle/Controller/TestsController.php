<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\State;

class TestsController extends ApiController
{
    private $siteRootUrl = null;
    
    
    public function startAction($site_root_url)
    {        
        $this->siteRootUrl = $site_root_url;         
        
        /* @var $jobService \SimplyTestable\ApiBundle\Services\JobService */
        $jobService = $this->get('simplytestable.services.jobservice');
        $job = $jobService->create(
            $this->getUser(),
            $this->getWebsite(),
            $this->getTaskTypes()
        );
        
        $this->container->get('simplytestable.services.resqueQueueService')->add(
            'SimplyTestable\ApiBundle\Resque\Job\JobPrepareJob',
            'job-prepare',
            array(
                'id' => $job->getId()
            )                
        );
        
        return $this->sendResponse($job);
    }    
    
    public function statusAction($site_root_url, $test_id)
    {        
        $this->siteRootUrl = $site_root_url;
        
        /* @var $jobService \SimplyTestable\ApiBundle\Services\JobService */
        $jobService = $this->get('simplytestable.services.jobservice');
        
        $job = $jobService->getEntityRepository()->findOneBy(array(
            'id' => $test_id,
            'user' => $this->getUser(),
            'website' => $this->getWebsite()
        ));    
        
        $job->setUrlTotal($this->container->get('simplytestable.services.taskservice')->getUrlCountByJob($job));
        
        if (is_null($job)) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;            
        }
        
        return $this->sendResponse($job);
    }
    
    public function cancelAction($site_root_url, $test_id)
    {        
        $this->siteRootUrl = $site_root_url;
        
        /* @var $jobService \SimplyTestable\ApiBundle\Services\JobService */
        $jobService = $this->get('simplytestable.services.jobservice');
        
        $job = $jobService->getEntityRepository()->findOneBy(array(
            'id' => $test_id,
            'user' => $this->getUser(),
            'website' => $this->getWebsite()
        ));        
        
        if (is_null($job)) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;            
        }
        
        $jobService->cancel($job);        
        return $this->sendSuccessResponse();
    }    
    
    public function resultsAction($site_root_url, $test_id)
    {
        return new \Symfony\Component\HttpFoundation\Response(json_encode(array(
            'site_root_url' => $site_root_url,
            'test_id' => $test_id
        )));
    }
    
    
    /**
     *
     * @return array
     */
    private function getTaskTypes() {
        return $this->getAllSelectableTaskTypes();
    }
    
    
    /**
     *
     * @return array
     */
    private function getAllSelectableTaskTypes() {
        return $this->getDoctrine()->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->findBy(array(
            'selectable' => true
        ));
    }
    
    
    /**
     *
     * @return boolean
     */
    private function isTestEnvironment() {
        return $this->get('kernel')->getEnvironment() == 'test';
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\User 
     */
    public function getUser() {
        if (!$this->isTestEnvironment()) {
            return parent::getUser();
        }
        
        return $this->get('simplytestable.services.userservice')->getPublicUser();
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\WebSite 
     */
    private function getWebsite() {        
        return $this->get('simplytestable.services.websiteservice')->fetch($this->siteRootUrl);
    }
}
