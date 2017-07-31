<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ExcludeByState;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ContentTest;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ExcludeByStateTest extends ContentTest
{
    /**
     * @var string[]
     */
    private $canonicalUrls = array(
        'http://one.example.com/',
        'http://two.example.com/',
        'http://three.example.com/'
    );

    protected function getRequestingUser()
    {
        return $this->getUserService()->getPublicUser();
    }

    protected function createJobs()
    {
        $jobFactory = new JobFactory($this->container);

        foreach ($this->canonicalUrls as $canonicalUrl) {
            $this->jobs[] = $jobFactory->createResolveAndPrepare([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
                JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            ]);
        }
    }

    protected function applyPreListChanges()
    {
        $this->jobs[0]->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($this->jobs[0]);

        $this->jobs[1]->setState($this->getJobService()->getRejectedState());
        $this->getJobService()->persistAndFlush($this->jobs[0]);
    }

    protected function getCanonicalUrls()
    {
        return $this->canonicalUrls;
    }

    protected function getExpectedCountValue()
    {
        return 1;
    }

    protected function getQueryParameters()
    {
        return array(
            'exclude-states' => array(
                'rejected',
                'queued'
            )
        );
    }
}
