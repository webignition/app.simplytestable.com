<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadAccountPlans extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

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
        $this->planRepository = $this->container->get('simplytestable.repository.accountplan');

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

    private function findPlanByNameHistory($names) {
        foreach ($names as $name) {
            $plan = $this->planRepository->findOneByName($name);
            if (!is_null($plan)) {
                return $plan;
            }
        }
    }


    /**
     *
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $plan
     * @param string $constraintName
     * @return Constraint
     */
    private function getConstraintFromPlanByName(Plan $plan, $constraintName) {
        foreach ($plan->getConstraints() as $constraint) {
            /* @var $constraint Constraint */
            if ($constraint->getName() == $constraintName) {
                return $constraint;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 6; // the order in which fixtures will be loaded
    }
}
