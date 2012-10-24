<?php
namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\EntityModificationMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\User;        

class Version20120906125906_add_admin_user extends EntityModificationMigration
{    
    public function postUp(Schema $schema) {
        $user = new User();
        $user->setEmail($this->container->getParameter('admin_user_email'));
        $user->setPlainPassword($this->container->getParameter('admin_user_password'));
        $user->setUsername('admin'); 
        $user->addRole('role_admin');
        
        $userManager = $this->container->get('fos_user.user_manager');        
        $userManager->updateUser($user);
        
        $manipulator = $this->container->get('fos_user.util.user_manipulator');
        $manipulator->activate($user->getUsername());
    }
}
