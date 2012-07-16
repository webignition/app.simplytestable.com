<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;

class TestsController extends Controller
{
    
    public function startAction()
    {
        $state = $this->get('simplytestable.services.stateservice')->fetch('job-new');
        
        if (!$this->hasNew()) {
            $job = new Job();
            $job->setUser($this->getUser());
            $job->setWebsite($this->getWebsite());
            $job->setState($state);
            
            $this->get('simplytestable.services.jobservice')->persistAndFlush($job);
        }
        
        $job = $this->get('simplytestable.services.jobservice')->getEntityRepository()->findOneBy(array(
            'user' => $this->getUser(),
            'website' => $this->getWebsite(),
            'state' => $state
        ));
        
        return new \Symfony\Component\HttpFoundation\Response(json_encode(array(
            'job_id' => $job->getId()
        )));
    }    
    
    public function statusAction($site_root_url, $test_id)
    {
        return new \Symfony\Component\HttpFoundation\Response(json_encode(array(
            'site_root_url' => $site_root_url,
            'test_id' => $test_id
        )));
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
     * @return \SimplyTestable\ApiBundle\Entity\WebSite 
     */
    private function getWebsite() {
        return $this->get('simplytestable.services.websiteservice')->fetch($this->getRequest()->get('site_root_url'));
    }
    
    
    /**
     *
     * @return boolean
     */
    private function hasNew() {        
        return count($this->get('simplytestable.services.jobservice')->getEntityRepository()->findBy(array(
            'user' => $this->getUser(),
            'website' => $this->getWebsite()
        ))) > 0;
    }
}
