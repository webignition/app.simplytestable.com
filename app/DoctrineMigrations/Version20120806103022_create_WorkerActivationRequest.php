<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120806103022_create_WorkerActivationRequest extends BaseMigration
{
    public function up(Schema $schema)
    {       
        
        $this->statements['mysql'] = array(
            "CREATE TABLE WorkerActivationRequest (
                id INT AUTO_INCREMENT NOT NULL,
                worker_id INT DEFAULT NULL,
                state_id INT NOT NULL,
                UNIQUE INDEX UNIQ_57FF325218F45C82 (worker_id),
                INDEX IDX_57FF32525D83CC1(state_id),
                PRIMARY KEY(id))
                DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
            "ALTER TABLE WorkerActivationRequest ADD CONSTRAINT FK_57FF325218F45C82 FOREIGN KEY (worker_id) REFERENCES Worker (id)",
            "ALTER TABLE WorkerActivationRequest ADD CONSTRAINT FK_57FF32525D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE WorkerActivationRequest (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                worker_id INT DEFAULT NULL,
                state_id INT NOT NULL,
                FOREIGN KEY(worker_id) REFERENCES Worker (id),
                FOREIGN KEY(state_id) REFERENCES State (id))",
            "CREATE UNIQUE INDEX UNIQ_57FF325218F45C82 ON WorkerActivationRequest (worker_id)",
            "CREATE INDEX IDX_57FF32525D83CC1 ON WorkerActivationRequest (state_id)"
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE WorkerActivationRequest");        
        parent::down($schema);
    }
}

