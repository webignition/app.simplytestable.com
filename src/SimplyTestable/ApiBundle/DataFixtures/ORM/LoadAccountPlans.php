<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

class LoadAccountPlans extends Fixture
{
    private $planDetails = array(
        array(
            'names' => array(
                'public'
            ),
            'visible' => false,
            'constraints'  => array(
                array(
                    'name' => 'full_site_jobs_per_site',
                    'limit' => 1
                ),
                array(
                    'name' => 'single_url_jobs_per_url',
                    'limit' => 1
                ),
                array(
                    'name' => 'urls_per_job',
                    'limit' => 10
                ),
                array(
                    'name' => 'task_types_per_job',
                    'limit' => 1
                )
            )
        ),
        array(
            'names' => array(
                'basic'
            ),
            'visible' => true,
            'constraints'  => array(
                array(
                    'name' => 'urls_per_job',
                    'limit' => 10
                ),
                array(
                    'name' => 'credits_per_month',
                    'limit' => 500
                )
            )
        ),
        array(
            'names' => array(
                'personal-9',
                'personal'
            ),
            'stripeId' => 'personal-9',
            'visible' => true,
            'isPremium' => true,
            'constraints'  => array(
                array(
                    'name' => 'urls_per_job',
                    'limit' => 50
                ),
                array(
                    'name' => 'credits_per_month',
                    'limit' => 5000
                )
            )
        ),
        array(
            'names' => array(
                'agency-19',
                'agency'
            ),
            'stripeId' => 'agency-19',
            'visible' => true,
            'isPremium' => true,
            'constraints'  => array(
                array(
                    'name' => 'urls_per_job',
                    'limit' => 250
                ),
                array(
                    'name' => 'credits_per_month',
                    'limit' => 20000
                )
            )
        ),
        array(
            'names' => array(
                'business-59',
                'business'
            ),
            'stripeId' => 'business-59',
            'visible' => true,
            'isPremium' => true,
            'constraints'  => array(
                array(
                    'name' => 'urls_per_job',
                    'limit' => 2500
                ),
                array(
                    'name' => 'credits_per_month',
                    'limit' => 100000
                )
            )
        ),
        array(
            'names' => array(
                'enterprise-299',
                'enterprise'
            ),
            'name' => 'enterprise',
            'stripeId' => 'enterprise-299',
            'isPremium' => true,
            'visible' => true,
            'constraints'  => array(
                array(
                    'name' => 'urls_per_job',
                    'limit' => 10000
                )
            )
        ),
        array(
            'names' => array(
                'WDS-custom'
            ),
            'name' => 'WDS-custom',
            'stripeId' => 'wds-2',
            'isPremium' => true,
            'visible' => false,
            'constraints'  => array(
                array(
                    'name' => 'urls_per_job',
                    'limit' => 8000
                ),
                array(
                    'name' => 'credits_per_month',
                    'limit' => 250000
                )
            )
        ),
    );

    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $planRepository = null;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->planRepository = $manager->getRepository(Plan::class);

        foreach ($this->planDetails as $planDetails) {
            $plan = $this->findPlanByNameHistory($planDetails['names']);

            if (is_null($plan)) {
                $plan = new Plan();
            }

            $planNames = $planDetails['names'];
            $plan->setName($planNames[count($planNames) - 1]);

            if (isset($planDetails['visible']) && $planDetails['visible'] === true) {
                $plan->setIsVisible(true);
            }

            if (isset($planDetails['isPremium']) && $planDetails['isPremium'] === true) {
                $plan->setIsPremium(true);
            }

            if (isset($planDetails['stripeId'])) {
                $plan->setStripeId($planDetails['stripeId']);
            }

            if (isset($planDetails['constraints'])) {
                foreach ($planDetails['constraints'] as $constraintDetails) {
                    $constraint = $this->getConstraintFromPlanByName($plan, $constraintDetails['name']);
                    $isNewConstraint = false;

                    if (is_null($constraint)) {
                        $constraint = new Constraint();
                        $isNewConstraint = true;
                    }

                    $constraint->setName($constraintDetails['name']);

                    if (isset($constraintDetails['limit'])) {
                        $constraint->setLimit($constraintDetails['limit']);
                    }

                    if (isset($constraintDetails['available'])) {
                        $constraint->setIsAvailable($constraintDetails['available']);
                    }

                    if ($isNewConstraint) {
                        $plan->addConstraint($constraint);
                    }
                }
            }

            $manager->persist($plan);
            $manager->flush();
        }
    }

    /**
     * @param $names
     *
     * @return Plan|null
     */
    private function findPlanByNameHistory($names)
    {
        foreach ($names as $name) {
            /* @var Plan $plan */
            $plan = $this->planRepository->findOneBy([
                'name' => $name,
            ]);

            if (!empty($plan)) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * @param Plan $plan
     * @param string $constraintName
     *
     * @return Constraint|null
     */
    private function getConstraintFromPlanByName(Plan $plan, $constraintName)
    {
        foreach ($plan->getConstraints() as $constraint) {
            /* @var $constraint Constraint */
            if ($constraint->getName() == $constraintName) {
                return $constraint;
            }
        }

        return null;
    }
}
