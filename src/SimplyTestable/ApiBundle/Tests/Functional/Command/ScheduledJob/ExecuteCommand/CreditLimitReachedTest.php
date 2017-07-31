<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CreditLimitReachedTest extends RejectedTest
{
    protected function preCall()
    {
        $jobFactory = new JobFactory($this->container);
        $userFactory = new UserFactory($this->container);

        $creditsPerMonth = 3;

        $user = $userFactory->create();
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
