<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130521154624_create_JobAmmendment extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE JobAmmendment (
                    id INT AUTO_INCREMENT NOT NULL,
                    job_id INT NOT NULL,
                    constraint_id INT DEFAULT NULL,
                    reason VARCHAR(255) NOT NULL,
                    INDEX IDX_E1E6DB74BE04EA9 (job_id),
                    INDEX IDX_E1E6DB74E3087FFC (constraint_id),
                    PRIMARY KEY(id))
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE JobAmmendment ADD CONSTRAINT FK_E1E6DB74BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
                "ALTER TABLE JobAmmendment ADD CONSTRAINT FK_E1E6DB74E3087FFC FOREIGN KEY (constraint_id) REFERENCES AccountPlanConstraint (id)"
            ],
            'down' => [
                "DROP TABLE JobAmmendment"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE JobAmmendment (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    job_id INT NOT NULL,
                    constraint_id INT DEFAULT NULL,
                    reason VARCHAR(255) NOT NULL,
                    FOREIGN KEY(job_id) REFERENCES Job (id),
                    FOREIGN KEY(constraint_id) REFERENCES AccountPlanConstraint (id))",
                "CREATE INDEX IDX_E1E6DB74BE04EA9 ON JobAmmendment (job_id)",
                "CREATE INDEX IDX_E1E6DB74E3087FFC ON JobAmmendment (constraint_id)"
            ],
            'down' => [
                "DROP TABLE JobAmmendment"
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