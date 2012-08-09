<?php

namespace Application\Migrations;

use SimplyTestable\BaseMigrationsBundle\Migration\BaseMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120809203611_create_WorkerLastTask extends BaseMigration
{
    public function up(Schema $schema)
    {       
        
        $this->statements['mysql'] = array(
            "CREATE TABLE WorkerLastTask (
                id INT AUTO_INCREMENT NOT NULL,
                worker_id INT DEFAULT NULL,
                task_id INT DEFAULT NULL,
                dateTime DATETIME DEFAULT NULL,
                UNIQUE INDEX UNIQ_334EE7796B20BA36 (worker_id),
                UNIQUE INDEX UNIQ_334EE7798DB60186 (task_id),
                PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
            "ALTER TABLE WorkerLastTask ADD CONSTRAINT FK_334EE7796B20BA36 FOREIGN KEY (worker_id) REFERENCES Worker (id)",
            "ALTER TABLE WorkerLastTask ADD CONSTRAINT FK_334EE7798DB60186 FOREIGN KEY (task_id) REFERENCES Task (id)"
        );
        
        $this->statements['sqlite'] = array(
            "CREATE TABLE WorkerLastTask (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                worker_id INT DEFAULT NULL,
                task_id INT DEFAULT NULL,
                dateTime DATETIME DEFAULT NULL,
                FOREIGN KEY(worker_id) REFERENCES Worker (id),
                FOREIGN KEY(task_id) REFERENCES Task (id))",
            "CREATE UNIQUE INDEX UNIQ_334EE7796B20BA36 ON WorkerLastTask (worker_id)",
            "CREATE UNIQUE INDEX UNIQ_334EE7798DB60186 ON WorkerLastTask (task_id)"
        );
        
        parent::up($schema);
    }

    public function down(Schema $schema)
    {
        $this->addCommonStatement("DROP TABLE WorkerLastTask");        
        parent::down($schema);
    }

}
