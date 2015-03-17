<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;

class SingleUrlLimitReachedTest extends RejectedTest {

    protected function preCall() {
        $user = $this->getUser();

        $this->getUserService()->setUser($user);

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');
        $constraintLimit = $constraint->getLimit();

        for ($i = 0; $i < $constraintLimit; $i++) {
            $job = $this->getJobService()->getById($this->createJobAndGetId(
                $this->getJobConfigurationWebsite(),
                $user->getEmail(),
                $this->getJobConfigurationJobType()
            ));
            $this->cancelJob($job);
        }

        parent::preCall();
    }

    /**
     * @return User
     */
    protected function getJobConfigurationUser() {
        return $this->getUserService()->getPublicUser();
    }

    /**
     * @return string
     */
    protected function getJobConfigurationJobType() {
        return 'single url';
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
