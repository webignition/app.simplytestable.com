<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20140707141202_create_Team extends BaseMigration
{
    public function up(Schema $schema)
    {
        $this->statements['mysql'] = array(
            "CREATE TABLE Team (
                id INT AUTO_INCREMENT NOT NULL,
                leader_id INT NOT NULL,
                INDEX IDX_64D20921A76ED395 (leader_id),
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
            "ALTER TABLE Team ADD CONSTRAINT FK_64D20921A76ED395 FOREIGN KEY (leader_id) REFERENCES fos_user (id)",
            "CREATE UNIQUE INDEX UNIQ_64D209215E237E06 ON Team (name)"
        );

        $this->statements['sqlite'] = array(
            "CREATE TABLE Team (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                leader_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                FOREIGN KEY(leader_id) REFERENCES fos_user (id)
             )",
            "CREATE UNIQUE INDEX UNIQ_64D209215E237E06 ON Team (name)",
            "CREATE INDEX IDX_64D20921A76ED395 ON Team (leader_id)"
        );

        parent::up($schema);
    }


    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE Team");
        parent::down($schema);
    }
}
