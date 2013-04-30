<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
 SimplyTestable\ApiBundle\Entity\Account\Plan\Plan, 
 SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint,
 Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130426095748_add_public_and_free_plans extends EntityModificationMigration
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
        )        
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
