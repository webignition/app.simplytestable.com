<?php

namespace SimplyTestable\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

class LoadAccountPlans extends AbstractFixture implements OrderedFixtureInterface
{    
    private $planDetails = array(
        array(
            'name' => 'public',
            'visible' => false,
            'constraints'  => array(
                array(
                    'name' => 'full_site_jobs_per_site',
                    'limit' => 10
                ),
                array(
                    'name' => 'single_url_jobs_per_url',
                    'limit' => 10
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
            'name' => 'basic',
            'visible' => true
        ),
        array(
            'name' => 'personal-9',
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
            'name' => 'agency-19',
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
            'name' => 'business-59',
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
            'name' => 'enterprise-299',
            'isPremium' => true,
            'visible' => true,
            'constraints'  => array(
                array(
                    'name' => 'urls_per_job',
                    'limit' => 10000
                )
            )
        ),        
    );    
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $planRepository = $manager->getRepository('SimplyTestable\ApiBundle\Entity\Account\Plan\Plan');        
        
        foreach ($this->planDetails as $planDetails) {
            $plan = $planRepository->findOneByName($planDetails['name']);
            
            if (is_null($plan)) {
                $plan = new Plan();
            }            
            
            $plan->setName($planDetails['name']);
            
            if (isset($planDetails['visible']) && $planDetails['visible'] === true) {
                $plan->setIsVisible(true);
            }
            
            if (isset($planDetails['isPremium']) && $planDetails['isPremium'] === true) {
                $plan->setIsPremium(true);
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
