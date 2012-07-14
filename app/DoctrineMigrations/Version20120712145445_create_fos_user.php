<?php
namespace Application\Migrations;

use webignition\ContainerAwareMigration\ContainerAwareMigration,
    Doctrine\DBAL\Schema\Schema,
    SimplyTestable\ApiBundle\Entity\User;

class Version20120712145445_create_fos_user extends ContainerAwareMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");        
        $this->addSql("CREATE TABLE fos_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, username_canonical VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, email_canonical VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, locked TINYINT(1) NOT NULL, expired TINYINT(1) NOT NULL, expires_at DATETIME DEFAULT NULL, confirmation_token VARCHAR(255) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT '(DC2Type:array)', credentials_expired TINYINT(1) NOT NULL, credentials_expire_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_957A647992FC23A8 (username_canonical), UNIQUE INDEX UNIQ_957A6479A0D96FBF (email_canonical), PRIMARY KEY(id)) ENGINE = InnoDB");
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");        
        $this->addSql("DROP TABLE fos_user");
    }
    
    public function postUp(Schema $schema) {
        $user = new User();
        $user->setEmail('public@simplytestable.com');
        $user->setPlainPassword('public');
        $user->setUsername('public');        
        
        $userManager = $this->container->get('fos_user.user_manager');        
        $userManager->updateUser($user);
        
        $manipulator = $this->container->get('fos_user.util.user_manipulator');
        $manipulator->activate($user->getUsername());
    }
}
