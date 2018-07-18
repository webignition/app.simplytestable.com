<?php

namespace App\Model\User\Summary;

use App\Entity\Account\Plan\Constraint;
use App\Entity\User;
use App\Entity\UserAccountPlan;

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
