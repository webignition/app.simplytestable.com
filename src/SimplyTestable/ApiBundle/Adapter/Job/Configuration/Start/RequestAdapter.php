<?php

namespace SimplyTestable\ApiBundle\Adapter\Job\Configuration\Start;

use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;

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
     * @var JobTypeService
     */
    private $jobTypeService;


    /**
     * @var JobConfiguration
     */
    private $jobConfiguration;


    public function __construct(
        Request $request,
        WebSiteService $webSiteService,
        JobTypeService $jobTypeService
    ) {
        $this->request = $request;
        $this->jobConfiguration = null;
        $this->websiteService = $webSiteService;
        $this->jobTypeService = $jobTypeService;
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
        $this->jobConfiguration->setWebsite($this->getRequestWebsite());
        $this->jobConfiguration->setType($this->getRequestJobType());
    }


    /**
     * @return WebSite
     */
    private function getRequestWebsite() {
        return $this->websiteService->fetch($this->request->attributes->get('site_root_url'));
    }


    /**
     * @return JobType
     */
    private function getRequestJobType() {
        if (!$this->jobTypeService->has($this->request->request->get('type'))) {
            return $this->jobTypeService->getDefaultType();
        }

        return $this->jobTypeService->getByName($this->request->request->get('type'));
    }
    
}