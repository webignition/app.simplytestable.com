<?php

namespace SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;

class Exception extends \Exception {

    const CODE_FULL_SITE_JOB_LIMIT_REACHED = 1;


    /**
     * @var AccountPlanConstraint
     */
    private $accountPlanConstraint;


    public function __construct($message, $code, AccountPlanConstraint $accountPlanConstraint) {
        parent::__construct($message, $code);
        $this->accountPlanConstraint = $accountPlanConstraint;
    }


    /**
     * @return AccountPlanConstraint
     */
    public function getAccountPlanConstraint() {
        return $this->accountPlanConstraint;
    }


    /**
     *
     * @return boolean
     */
    public function isFullSiteJobLimitReachedExcepton() {
        return $this->getCode() === self::CODE_FULL_SITE_JOB_LIMIT_REACHED;
    }
}