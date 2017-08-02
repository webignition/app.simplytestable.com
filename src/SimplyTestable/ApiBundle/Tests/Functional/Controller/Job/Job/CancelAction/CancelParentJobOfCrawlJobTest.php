<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\CancelAction;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CancelParentJobOfCrawlJobTest extends IsCancelledTest
{
    private $user;

    /**
     * @var CrawlJobContainer
     */
    private $crawlJobContainer;

    protected function preCall()
    {
        $this->getUserService()->setUser($this->getUser());

        $this->crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($this->job);
        $this->getCrawlJobContainerService()->prepare($this->crawlJobContainer);
    }


    public function testCrawlJobIsCancelled()
    {
        $this->assertEquals(
            JobService::CANCELLED_STATE,
            $this->crawlJobContainer->getCrawlJob()->getState()->getName()
        );
    }

    protected function getJob()
    {
        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->create([
            JobFactory::KEY_USER => $this->getUser(),
        ]);

        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobFailedNoSitemapState = $stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE);

        $job->setState($jobFailedNoSitemapState);
        $this->getJobService()->persistAndFlush($job);

        return $job;
    }

    protected function getExpectedJobStartingState()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobFailedNoSitemapState = $stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE);

        return $jobFailedNoSitemapState;
    }

    protected function getExpectedResponseCode()
    {
        return 200;
    }

    private function getUser()
    {
        if (is_null($this->user)) {
            $userFactory = new UserFactory($this->container);
            $this->user = $userFactory->createAndActivateUser();
        }

        return $this->user;
    }
}
