<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class WebsitesTest extends BaseControllerJsonTestCase
{
    /**
      * @var Job[]
     */
    protected $jobs = array();

    public function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getRequestingUser());

        $this->createJobs();
        $this->applyPreListChanges();
    }

    abstract protected function getRequestingUser();
    abstract protected function getCanonicalUrls();

    protected function applyPreListChanges()
    {
    }

    protected function createJobs()
    {
        $jobFactory = $this->createJobFactory();
        foreach ($this->getCanonicalUrls() as $canonicalUrl) {
            $this->jobs[] = $jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            ]);
        }
    }

    protected function getCanonicalUrlCollection($count = 1)
    {
        $canonicalUrlCollection = array();

        for ($index = 0; $index < $count; $index++) {
            $canonicalUrlCollection[] = 'http://' . ($index + 1) . '.example.com/';
        }

        return $canonicalUrlCollection;
    }
}
