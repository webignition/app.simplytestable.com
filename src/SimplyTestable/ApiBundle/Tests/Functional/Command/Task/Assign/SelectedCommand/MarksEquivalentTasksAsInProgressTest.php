<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign\SelectedCommand;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class MarksEquivalentTasksAsInProgressTest extends CommandTest
{
    public function testCommand()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $this->setJobTypeConstraintLimits();
        $this->createWorker();

        $jobFactory = $this->createJobFactory();

        $job1 = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['css validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'css validation' => [
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                ],
            ],
        ]);

        $job2 = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['html validation', 'css validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'css validation' => [
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                ],
            ]
        ]);

        $this->queueTaskAssignCollectionResponseHttpFixture();

        $task = $job1->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        $assignReturnCode = $this->execute();

        $this->assertEquals(0, $assignReturnCode);
        $this->assertEquals($this->getTaskService()->getInProgressState(), $job1->getTasks()->get(0)->getState());
        $this->assertEquals($this->getTaskService()->getInProgressState(), $job2->getTasks()->get(1)->getState());
        $this->assertEquals($this->getJobService()->getInProgressState(), $job1->getState());
        $this->assertEquals($this->getJobService()->getInProgressState(), $job2->getState());
    }

    private function setJobTypeConstraintLimits()
    {
        $this->getJobUserAccountPlanEnforcementService()->setUser($this->getUserService()->getPublicUser());

        $fullSiteJobsPerSiteConstraint =
            $this->getJobUserAccountPlanEnforcementService()->getFullSiteJobLimitConstraint();

        $singleUrlJobsPerUrlConstraint =
            $this->getJobUserAccountPlanEnforcementService()->getSingleUrlJobLimitConstraint();

        $fullSiteJobsPerSiteConstraint->setLimit(2);
        $singleUrlJobsPerUrlConstraint->setLimit(2);

        $this->getJobService()->getManager()->persist($fullSiteJobsPerSiteConstraint);
        $this->getJobService()->getManager()->persist($singleUrlJobsPerUrlConstraint);
    }
}
