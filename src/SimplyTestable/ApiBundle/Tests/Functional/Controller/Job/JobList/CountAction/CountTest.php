<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class CountTest extends BaseControllerJsonTestCase
{
    /**
     * @var Job[]
     */
    protected $jobs = array();

    protected function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getRequestingUser());

        $this->createJobs();
        $this->applyPreListChanges();
    }

    abstract protected function getCanonicalUrls();
    abstract protected function getRequestingUser();

    protected function applyPreListChanges()
    {
    }

    protected function createJobs()
    {
        $jobFactory = new JobFactory($this->container);

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
