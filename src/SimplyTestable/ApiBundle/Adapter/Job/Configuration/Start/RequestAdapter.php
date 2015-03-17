<?php

namespace SimplyTestable\ApiBundle\Adapter\Job\Configuration\Start;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\WebSiteService;

class RequestAdapter {

    /**
     * @var Request
     */
    private $request;


    /**
     * @var WebSiteService
     */
    private $websiteService;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    /**
     * @param Request $request
     * @return $this
     */
    public function setRequest(Request $request) {
        $this->request = $request;
        $this->jobConfiguration = null;
        return $this;
    }


    /**
     * @param WebSiteService $webSiteService
     * @return $this
     */
    public function setWebsiteService(WebSiteService $webSiteService) {
        $this->websiteService = $webSiteService;
        return $this;
    }


    /**
     * @return JobConfiguration
     */
    public function getJobConfiguration() {
        if (is_null($this->jobConfiguration)) {
            $this->build();
        }

        return $this->jobConfiguration;
    }


    private function build() {
        $this->jobConfiguration = new JobConfiguration();
        $this->jobConfiguration->setWebsite($this->websiteService->fetch($this->request->attributes->get('site_root_url')));
    }
    
}