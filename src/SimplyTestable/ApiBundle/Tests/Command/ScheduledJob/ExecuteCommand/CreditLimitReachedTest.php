<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;

class CreditLimitReachedTest extends RejectedTest {

    protected function preCall() {
        $creditsPerMonth = 10;

        $user = $this->getUser();

        $this->getUserService()->setUser($user);

        $this->getAccountPlanService()->find('basic')->getConstraintNamed('credits_per_month')->setLimit($creditsPerMonth);

        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
            $this->getTestUser()->getEmail())
        );
        $this->setJobTasksCompleted($job);
        $this->completeJob($job);

        parent::preCall();
    }

    protected function getExpectedReturnCode() {
        return ExecuteCommand::RETURN_CODE_PLAN_LIMIT_REACHED;
    }

    protected function getJobListIndex() {
        return 1;
    }

    protected function getExpectedRejectionReason()
    {
        return 'plan-constraint-limit-reached';
    }
}
