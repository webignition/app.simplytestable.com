<?php

namespace SimplyTestable\ApiBundle\Model\User\Summary;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Model\User\Summary\PlanConstraints as PlanConstraintsSummary;
use SimplyTestable\ApiBundle\Model\User\Summary\StripeCustomer as StripeCustomerSummary;
use SimplyTestable\ApiBundle\Model\User\Summary\Team as TeamSummary;

class Summary implements \JsonSerializable
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var UserAccountPlan
     */
    private $userAccountPlan;

    /**
     * @var StripeCustomerSummary
     */
    private $stripeCustomerSummary;

    /**
     * @var PlanConstraintsSummary
     */
    private $planConstraintsSummary;

    /**
     * @var TeamSummary
     */
    private $teamSummary;

    /**
     * @param User $user
     * @param UserAccountPlan $userAccountPlan
     * @param StripeCustomerSummary $stripeCustomerSummary
     * @param PlanConstraints $planConstraintsSummary
     * @param TeamSummary $teamSummary
     */
    public function __construct(
        User $user,
        UserAccountPlan $userAccountPlan,
        StripeCustomerSummary $stripeCustomerSummary,
        PlanConstraintsSummary $planConstraintsSummary,
        TeamSummary $teamSummary
    ) {
        $this->user = $user;
        $this->userAccountPlan = $userAccountPlan;
        $this->stripeCustomerSummary = $stripeCustomerSummary;
        $this->planConstraintsSummary = $planConstraintsSummary;
        $this->teamSummary = $teamSummary;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $userSummaryData = array_merge(
            $this->user->jsonSerialize(),
            [
                'user_plan' => $this->userAccountPlan->jsonSerialize()
            ]
        );

        if (!$this->stripeCustomerSummary->isEmpty()) {
            $userSummaryData['stripe_customer'] = $this->stripeCustomerSummary->jsonSerialize();
        }

        $userSummaryData['plan_constraints'] = $this->planConstraintsSummary->jsonSerialize();
        $userSummaryData['team_summary'] = $this->teamSummary->jsonSerialize();

        return $userSummaryData;
    }
}
