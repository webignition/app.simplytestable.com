<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120809203611_create_WorkerTaskAssignment extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE WorkerTaskAssignment (
                    id INT AUTO_INCREMENT NOT NULL,
                    worker_id INT DEFAULT NULL,
                    task_id INT DEFAULT NULL,
                    dateTime DATETIME DEFAULT NULL,
                    UNIQUE INDEX UNIQ_334EE7796B20BA36 (worker_id),
                    UNIQUE INDEX UNIQ_334EE7798DB60186 (task_id),
                    PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
                "ALTER TABLE WorkerTaskAssignment ADD CONSTRAINT FK_334EE7796B20BA36 FOREIGN KEY (worker_id) REFERENCES Worker (id)",
                "ALTER TABLE WorkerTaskAssignment ADD CONSTRAINT FK_334EE7798DB60186 FOREIGN KEY (task_id) REFERENCES Task (id)"
            ],
            'down' => [
                "DROP TABLE WorkerTaskAssignment"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE WorkerTaskAssignment (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    worker_id INT DEFAULT NULL,
                    task_id INT DEFAULT NULL,
                    dateTime DATETIME DEFAULT NULL,
                    FOREIGN KEY(worker_id) REFERENCES Worker (id),
                    FOREIGN KEY(task_id) REFERENCES Task (id))",
                "CREATE UNIQUE INDEX UNIQ_334EE7796B20BA36 ON WorkerTaskAssignment (worker_id)",
                "CREATE UNIQUE INDEX UNIQ_334EE7798DB60186 ON WorkerTaskAssignment (task_id)"
            ],
            'down' => [
                "DROP TABLE WorkerTaskAssignment"
            ]
        ]
    ];

    public function up(Schema $schema)
    {
        foreach ($this->statements[$this->connection->getDatabasePlatform()->getName()]['up'] as $statement) {
            $this->addSql($statement);
        }
    }

    public function down(Schema $schema)
    {
        foreach ($this->statements[$this->connection->getDatabasePlatform()->getName()]['down'] as $statement) {
            $this->addSql($statement);
        }
    }

}