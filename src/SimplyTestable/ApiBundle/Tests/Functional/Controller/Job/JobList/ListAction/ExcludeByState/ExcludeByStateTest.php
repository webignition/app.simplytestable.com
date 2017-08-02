<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeByState;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ListContentTest;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class ExcludeByStateTest extends ListContentTest
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
            ], [
                'prepare' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    HttpFixtureFactory::createStandardSitemapResponse($canonicalUrl),
                ],
            ]);
        }
    }

    protected function applyPreListChanges()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobCompletedState = $stateService->fetch(JobService::COMPLETED_STATE);
        $jobRejectedState = $stateService->fetch(JobService::REJECTED_STATE);

        $this->jobs[0]->setState($jobCompletedState);
        $this->getJobService()->persistAndFlush($this->jobs[0]);

        $this->jobs[1]->setState($jobRejectedState);
        $this->getJobService()->persistAndFlush($this->jobs[0]);
    }

    protected function getCanonicalUrls()
    {
        return $this->canonicalUrls;
    }

    protected function getExpectedJobListUrls()
    {
        return array(
            'http://one.example.com/'
        );
    }

    protected function getExpectedListLength()
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


