<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\CancelAction;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CrawlJobTest extends IsCancelledTest
{
    /**
     * @var Job
     */
    private $parentJob;

    /**
     * @var User
     */
    private $user;

    /**
     * @var CrawlJobContainer
     */
    private $crawlJobContainer;

    protected function preCall()
    {
        $this->getUserService()->setUser($this->getUser());
    }

    public function testParentJobIsQueued()
    {
        $this->assertEquals(JobService::QUEUED_STATE, $this->parentJob->getState()->getName());
    }

    public function testParentJobTaskHasCrawlJobCssValidationDomainsToIgnore()
    {
        /* @var $task Task */
        $task = null;
        while (!$task) {
            foreach ($this->parentJob->getTasks() as $currentTask) {
                if ($currentTask->getType()->getName() == 'CSS validation') {
                    $task = $currentTask;
                }
            }
        }

        $this->assertEquals(
            $this->container->getParameter('css-validation-domains-to-ignore'),
            json_decode($task->getParameters())->{'domains-to-ignore'}
        );
    }

    public function testParentJobTaskHasCrawlJobJsStaticAnalysisDomainsToIgnore()
    {
        /* @var $task Task */
        $task = null;
        while (!$task) {
            foreach ($this->parentJob->getTasks() as $currentTask) {
                if ($currentTask->getType()->getName() == 'JS static analysis') {
                    $task = $currentTask;
                }
            }
        }

        $this->assertEquals(
            $this->container->getParameter('js-static-analysis-domains-to-ignore'),
            json_decode($task->getParameters())->{'domains-to-ignore'}
        );
    }

    public function testResqueTasksNotifyJobIsCreated()
    {
        $this->assertFalse($this->getResqueQueueService()->isEmpty(
            'tasks-notify'
        ));
    }

    protected function getJob()
    {
        $jobFactory = new JobFactory($this->container);

        $this->parentJob = $jobFactory->create([
            JobFactory::KEY_TEST_TYPES => ['CSS validation', 'JS static analysis'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'CSS validation' => ['ignore-common-cdns' => 1],
                'JS static analysis' => ['ignore-common-cdns' => 1]
            ],
            JobFactory::KEY_USER => $this->getUser(),
        ]);

        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobFailedNoSitemapState = $stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE);

        $this->parentJob->setState($jobFailedNoSitemapState);
        $this->getJobService()->persistAndFlush($this->parentJob);

        $this->crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($this->parentJob);

        $this->getCrawlJobContainerService()->prepare($this->crawlJobContainer);

        return $this->crawlJobContainer->getCrawlJob();
    }

    protected function getExpectedJobStartingState()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        return $stateService->fetch(JobService::QUEUED_STATE);
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
