<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120806103022_create_WorkerActivationRequest extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE WorkerActivationRequest (
                    id INT AUTO_INCREMENT NOT NULL,
                    worker_id INT DEFAULT NULL,
                    state_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    UNIQUE INDEX UNIQ_57FF325218F45C82 (worker_id),
                    INDEX IDX_57FF32525D83CC1(state_id),
                    PRIMARY KEY(id))
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB",
                "ALTER TABLE WorkerActivationRequest ADD CONSTRAINT FK_57FF325218F45C82 FOREIGN KEY (worker_id) REFERENCES Worker (id)",
                "ALTER TABLE WorkerActivationRequest ADD CONSTRAINT FK_57FF32525D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)"
            ],
            'down' => [
                "DROP TABLE WorkerActivationRequest"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE WorkerActivationRequest (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    worker_id INT DEFAULT NULL,
                    state_id INT NOT NULL,
                    token VARCHAR(255) NOT NULL,
                    FOREIGN KEY(worker_id) REFERENCES Worker (id),
                    FOREIGN KEY(state_id) REFERENCES State (id))",
                "CREATE UNIQUE INDEX UNIQ_57FF325218F45C82 ON WorkerActivationRequest (worker_id)",
                "CREATE INDEX IDX_57FF32525D83CC1 ON WorkerActivationRequest (state_id)"
            ],
            'down' => [
                "DROP TABLE WorkerActivationRequest"
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