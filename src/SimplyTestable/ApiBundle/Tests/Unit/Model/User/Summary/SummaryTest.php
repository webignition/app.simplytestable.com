<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\User\Summary;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Model\User\Summary\Summary;
use SimplyTestable\ApiBundle\Model\User\Summary\PlanConstraints as PlanConstraintsSummary;
use SimplyTestable\ApiBundle\Model\User\Summary\StripeCustomer as StripeCustomerSummary;
use SimplyTestable\ApiBundle\Model\User\Summary\Team as TeamSummary;
use SimplyTestable\ApiBundle\Tests\Factory\ModelFactory;

class SummaryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param User $user
     * @param UserAccountPlan $userAccountPlan
     * @param StripeCustomerSummary $stripeCustomerSummary
     * @param PlanConstraintsSummary $planConstraintsSummary
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(
        User $user,
        UserAccountPlan $userAccountPlan,
        StripeCustomerSummary $stripeCustomerSummary,
        PlanConstraintsSummary $planConstraintsSummary,
        TeamSummary $teamSummary,
        $expectedReturnValue
    ) {
        $userSummary = new Summary(
            $user,
            $userAccountPlan,
            $stripeCustomerSummary,
            $planConstraintsSummary,
            $teamSummary
        );

        $serializedUserSummary = $userSummary->jsonSerialize();

        if (isset($serializedUserSummary['stripe_customer'])) {
            $serializedUserSummary['stripe_customer'] = [
                'id' => $serializedUserSummary['stripe_customer']['id'],
            ];
        }

        $this->assertEquals($expectedReturnValue, $serializedUserSummary);
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        $basicAccountPlan = ModelFactory::createAccountPlan([
            ModelFactory::ACCOUNT_PLAN_NAME => 'basic',
            ModelFactory::ACCOUNT_PLAN_IS_PREMIUM => false,
            ModelFactory::ACCOUNT_PLAN_CONSTRAINTS => [
                ModelFactory::createAccountPlanConstraint([
                    ModelFactory::CONSTRAINT_NAME => 'urls_per_job',
                    ModelFactory::CONSTRAINT_LIMIT => 10,
                ]),
            ],
        ]);

        $personalAccountPlan = ModelFactory::createAccountPlan([
            ModelFactory::ACCOUNT_PLAN_NAME => 'personal',
            ModelFactory::ACCOUNT_PLAN_IS_PREMIUM => true,
            ModelFactory::ACCOUNT_PLAN_CONSTRAINTS => [
                ModelFactory::createAccountPlanConstraint([
                    ModelFactory::CONSTRAINT_NAME => 'urls_per_job',
                    ModelFactory::CONSTRAINT_LIMIT => 10,
                ]),
                ModelFactory::createAccountPlanConstraint([
                    ModelFactory::CONSTRAINT_NAME => 'credits_per_month',
                    ModelFactory::CONSTRAINT_LIMIT => 250,
                ]),
            ],
        ]);

        $basicUserAccountPlan = ModelFactory::createUserAccountPlan([
            ModelFactory::USER_ACCOUNT_PLAN_PLAN => $basicAccountPlan,
            ModelFactory::USER_ACCOUNT_PLAN_START_TRIAL_PERIOD => 30,
        ]);

        $personalUserAccountPlan = ModelFactory::createUserAccountPlan([
            ModelFactory::USER_ACCOUNT_PLAN_PLAN => $personalAccountPlan,
            ModelFactory::USER_ACCOUNT_PLAN_START_TRIAL_PERIOD => 30,
        ]);

        return [
            'user account plan has no stripe customer' => [
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'userAccountPlan' => $basicUserAccountPlan,
                'stripeCustomerSummary' => new StripeCustomerSummary(),
                'planConstraintsSummary' => new PlanConstraintsSummary($basicUserAccountPlan, 0),
                'teamSummary' => new TeamSummary(false, false),
                'expectedReturnValue' => [
                    'email' => 'user@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 10,
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                ],
            ],
            'user account plan has stripe customer' => [
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'userAccountPlan' => $basicUserAccountPlan,
                'stripeCustomerSummary' => new StripeCustomerSummary(
                    ModelFactory::createStripeCustomerModel(
                        'customer-nocard-nosub',
                        [
                            '{stripe-customer-id}' => 'cus_aaaaaaaaaaaaa0',
                        ]
                    )
                ),
                'planConstraintsSummary' => new PlanConstraintsSummary($personalUserAccountPlan, 20),
                'teamSummary' => new TeamSummary(false, false),
                'expectedReturnValue' => [
                    'email' => 'user@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'credits' => [
                            'limit' => 250,
                            'used' => 20,
                        ],
                        'urls_per_job' => 10,
                    ],
                    'stripe_customer' => [
                        'id' => 'cus_aaaaaaaaaaaaa0',
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => false,
                    ],
                ],
            ],
            'not in team, has invite' => [
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'userAccountPlan' => $basicUserAccountPlan,
                'stripeCustomerSummary' => new StripeCustomerSummary(),
                'planConstraintsSummary' => new PlanConstraintsSummary($basicUserAccountPlan, 0),
                'teamSummary' => new TeamSummary(false, true),
                'expectedReturnValue' => [
                    'email' => 'user@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 10,
                    ],
                    'team_summary' => [
                        'in' => false,
                        'has_invite' => true,
                    ],
                ],
            ],
            'in team' => [
                'user' => ModelFactory::createUser([
                    ModelFactory::USER_EMAIL => 'user@example.com',
                ]),
                'userAccountPlan' => $basicUserAccountPlan,
                'stripeCustomerSummary' => new StripeCustomerSummary(),
                'planConstraintsSummary' => new PlanConstraintsSummary($basicUserAccountPlan, 0),
                'teamSummary' => new TeamSummary(true, false),
                'expectedReturnValue' => [
                    'email' => 'user@example.com',
                    'user_plan' => [
                        'plan' => [
                            'name' => 'basic',
                            'is_premium' => false,
                        ],
                        'start_trial_period' => 30,
                    ],
                    'plan_constraints' => [
                        'urls_per_job' => 10,
                    ],
                    'team_summary' => [
                        'in' => true,
                        'has_invite' => false,
                    ],
                ],
            ],
        ];
    }
}
