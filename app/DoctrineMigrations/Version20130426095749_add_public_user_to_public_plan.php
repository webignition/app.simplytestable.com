<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
 SimplyTestable\ApiBundle\Entity\Account\Plan\Plan, 
 SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint,
 Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20130426095749_add_public_user_to_public_plan extends EntityModificationMigration
{

    
    public function postUp(Schema $schema)
    {
        $user = $this->getUserService()->getPublicUser();
        $plan = $this->getAccountPlanService()->find('public');
        
        $this->getUserAccountPlanService()->create($user, $plan);
    }
    
    public function postDown(Schema $schema)
    {      
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private function getUserAccountPlanService() {
        return $this->container->get('simplytestable.services.useraccountplanservice');
    }     
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\AccountPlanService
     */
    private function getAccountPlanService() {
        return $this->container->get('simplytestable.services.accountplanservice');
    }    
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\UserService
     */
    private function getUserService() {
        return $this->container->get('simplytestable.services.userservice');
    }
}
