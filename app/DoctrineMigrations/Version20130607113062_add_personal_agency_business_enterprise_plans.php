<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
 SimplyTestable\ApiBundle\Entity\Account\Plan\Plan, 
 SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint,
 Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130607113062_add_personal_agency_business_enterprise_plans extends EntityModificationMigration
{
    private $planDetails = array(
        array(
            'name' => 'personal-9',
            'visible' => true,
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
            'visible' => true,
            'constraints'  => array(
                array(
                    'name' => 'urls_per_job',
                    'limit' => 10000
                )
            )
        ),        
    );
    
    public function postUp(Schema $schema)
    {
        foreach ($this->planDetails as $planDetails) {
            $plan = new Plan();
            $plan->setName($planDetails['name']);
            
            if (isset($planDetails['visible']) && $planDetails['visible'] === true) {
                $plan->setIsVisible(true);
            }
            
            if (isset($planDetails['constraints'])) {
                foreach ($planDetails['constraints'] as $constraintDetails) {
                    $constraint = new Constraint();
                    $constraint->setName($constraintDetails['name']);
                    
                    if (isset($constraintDetails['limit'])) {
                        $constraint->setLimit($constraintDetails['limit']);
                    }
                    
                    if (isset($constraintDetails['available'])) {
                        $constraint->setIsAvailable($constraintDetails['available']);
                    }                    
                    
                    $plan->addConstraint($constraint);
                }
            }
            
            $this->getEntityManager()->persist($plan);
            $this->getEntityManager()->flush();              
        }
    }
    
    public function postDown(Schema $schema)
    {      
    }
}
