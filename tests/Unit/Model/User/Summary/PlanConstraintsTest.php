<?php

namespace App\Tests\Unit\User\Summary;

use App\Entity\UserAccountPlan;
use App\Model\User\Summary\PlanConstraints;
use App\Tests\Factory\ModelFactory;

class PlanConstraintsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param UserAccountPlan $userAccountPlan
     * @param int $creditsUsedThisMonth
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(
        UserAccountPlan $userAccountPlan,
        $creditsUsedThisMonth,
        $expectedReturnValue
    ) {
        $planConstraintsSummary = new PlanConstraints($userAccountPlan, $creditsUsedThisMonth);

        $this->assertEquals($expectedReturnValue, $planConstraintsSummary->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'no plan constraints' => [
                'userAccountPlan' => ModelFactory::createUserAccountPlan([
                    ModelFactory::USER_ACCOUNT_PLAN_PLAN => ModelFactory::createAccountPlan([
                        ModelFactory::ACCOUNT_PLAN_NAME => 'basic',
                        ModelFactory::ACCOUNT_PLAN_IS_PREMIUM => false,
                    ]),
                    ModelFactory::USER_ACCOUNT_PLAN_START_TRIAL_PERIOD => 30,
                ]),
                'creditsUsedThisMonth' => null,
                'expectedReturnValue' => [],
            ],
            'has urls_per_job constraint' => [
                'userAccountPlan' => ModelFactory::createUserAccountPlan([
                    ModelFactory::USER_ACCOUNT_PLAN_PLAN => ModelFactory::createAccountPlan([
                        ModelFactory::ACCOUNT_PLAN_NAME => 'basic',
                        ModelFactory::ACCOUNT_PLAN_IS_PREMIUM => false,
                        ModelFactory::ACCOUNT_PLAN_CONSTRAINTS => [
                            ModelFactory::createAccountPlanConstraint([
                                ModelFactory::CONSTRAINT_NAME => 'urls_per_job',
                                ModelFactory::CONSTRAINT_LIMIT => 10,
                            ]),
                        ],
                    ]),
                    ModelFactory::USER_ACCOUNT_PLAN_START_TRIAL_PERIOD => 30,
                ]),
                'creditsUsedThisMonth' => 0,
                'expectedReturnValue' => [
                    'urls_per_job' => 10,
                ],
            ],
            'has urls_per_job and credits_per_month constraints' => [
                'userAccountPlan' => ModelFactory::createUserAccountPlan([
                    ModelFactory::USER_ACCOUNT_PLAN_PLAN => ModelFactory::createAccountPlan([
                        ModelFactory::ACCOUNT_PLAN_NAME => 'basic',
                        ModelFactory::ACCOUNT_PLAN_IS_PREMIUM => false,
                        ModelFactory::ACCOUNT_PLAN_CONSTRAINTS => [
                            ModelFactory::createAccountPlanConstraint([
                                ModelFactory::CONSTRAINT_NAME => 'credits_per_month',
                                ModelFactory::CONSTRAINT_LIMIT => 250,
                            ]),
                            ModelFactory::createAccountPlanConstraint([
                                ModelFactory::CONSTRAINT_NAME => 'urls_per_job',
                                ModelFactory::CONSTRAINT_LIMIT => 10,
                            ]),
                        ],
                    ]),
                    ModelFactory::USER_ACCOUNT_PLAN_START_TRIAL_PERIOD => 30,
                ]),
                'creditsUsedThisMonth' => 20,
                'expectedReturnValue' => [
                    'credits' => [
                        'limit' => 250,
                        'used' => 20,
                    ],
                    'urls_per_job' => 10,
                ],
            ],
        ];
    }
}
