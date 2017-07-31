<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class CreditLimitReachedTest extends RejectedTest
{
    protected function preCall()
    {
        $jobFactory = new JobFactory($this->container);

        $creditsPerMonth = 3;

        $user = $this->getTestUser();
        $this->getUserService()->setUser($user);

        $this
            ->getAccountPlanService()
            ->find('basic')
            ->getConstraintNamed('credits_per_month')
            ->setLimit($creditsPerMonth);

        $job = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ]);

        $this->completeJob($job);

        parent::preCall();
    }

    protected function getExpectedReturnCode()
    {
        return ExecuteCommand::RETURN_CODE_PLAN_LIMIT_REACHED;
    }

    protected function getJobListIndex()
    {
        return 1;
    }

    protected function getExpectedRejectionReason()
    {
        return 'plan-constraint-limit-reached';
    }
}
