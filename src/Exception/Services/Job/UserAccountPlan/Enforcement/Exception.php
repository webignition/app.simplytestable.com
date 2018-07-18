<?php

namespace App\Exception\Services\Job\UserAccountPlan\Enforcement;

use App\Entity\Account\Plan\Constraint as AccountPlanConstraint;

class Exception extends \Exception {

    const CODE_FULL_SITE_JOB_LIMIT_REACHED = 1;
    const CODE_SINGLE_URL_JOB_LIMIT_REACHED = 2;
    const CODE_CREDIT_LIMIT_REACHED = 3;


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
    public function isFullSiteJobLimitReachedException() {
        return $this->getCode() === self::CODE_FULL_SITE_JOB_LIMIT_REACHED;
    }


    /**
     *
     * @return boolean
     */
    public function isSingleUrlJobLimitReachedException() {
        return $this->getCode() === self::CODE_SINGLE_URL_JOB_LIMIT_REACHED;
    }


    /**
     * @return bool
     */
    public function isJobLimitReachedException() {
        return $this->isFullSiteJobLimitReachedException() || $this->isSingleUrlJobLimitReachedException();
    }


    /**
     * @return bool
     */
    public function isCreditLimitReachedException() {
        return $this->getCode() == self::CODE_CREDIT_LIMIT_REACHED;
    }
}