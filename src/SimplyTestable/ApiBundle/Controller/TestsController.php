<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;

class TestsController extends Controller
{
    public function startAction($site_root_url)
    {        
        $job = new Job();
        $job->setUser($this->getUser());
        $job->setWebsite($this->getWebsite($site_root_url));
        var_dump($job);
        exit();
        
        return new \Symfony\Component\HttpFoundation\Response(json_encode(array(
            'site_root_url' => $site_root_url
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
     * @param string $site_root_url
     * @return \SimplyTestable\ApiBundle\Entity\WebSite 
     */
    private function getWebsite($site_root_url) {
        return $this->get('simplytestable.services.websiteservice')->fetch($site_root_url);
    }
}
