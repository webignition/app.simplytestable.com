<?php

namespace AppBundle\Model\User\Summary;

use AppBundle\Entity\Account\Plan\Constraint;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAccountPlan;

class PlanConstraints implements \JsonSerializable
{
    /**
     * @var UserAccountPlan
     */
    private $userAccountPlan;

    /**
     * @var int
     */
    private $creditsUsedThisMonth;

    /**
     * @param UserAccountPlan $userAccountPlan
     * @param int $creditsUsedThisMonth
     */
    public function __construct(
        UserAccountPlan $userAccountPlan,
        $creditsUsedThisMonth
    ) {
        $this->userAccountPlan = $userAccountPlan;
        $this->creditsUsedThisMonth = $creditsUsedThisMonth;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $planConstraints = [];

        $plan = $this->userAccountPlan->getPlan();

        $creditsPerMonthConstraint = $plan->getConstraintNamed('credits_per_month');
        if (!empty($creditsPerMonthConstraint)) {
            $planConstraints['credits'] = [
                'limit' => $creditsPerMonthConstraint->getLimit(),
                'used' => $this->creditsUsedThisMonth,
            ];
        }

        $urlsPerJobConstraint = $plan->getConstraintNamed('urls_per_job');
        if (!empty($urlsPerJobConstraint)) {
            $planConstraints['urls_per_job'] = $urlsPerJobConstraint->getLimit();
        }

        $userSummaryData['plan_constraints'] = $planConstraints;

        return $planConstraints;
    }
}
