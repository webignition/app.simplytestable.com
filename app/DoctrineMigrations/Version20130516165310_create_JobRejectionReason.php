<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130516165310_create_JobRejectionReason extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE JobRejectionReason (
                    id INT AUTO_INCREMENT NOT NULL,
                    job_id INT DEFAULT NULL,
                    constraint_id INT DEFAULT NULL,
                    reason VARCHAR(255) NOT NULL,
                    UNIQUE INDEX UNIQ_F769EE08BE04EA9 (job_id),
                    INDEX IDX_F769EE08E3087FFC (constraint_id),
                    PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE JobRejectionReason ADD CONSTRAINT FK_F769EE08BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
                "ALTER TABLE JobRejectionReason ADD CONSTRAINT FK_F769EE08E3087FFC FOREIGN KEY (constraint_id) REFERENCES AccountPlanConstraint (id)"
            ],
            'down' => [
                "DROP TABLE JobRejectionReason"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE JobRejectionReason (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    job_id INT DEFAULT NULL,
                    constraint_id INT DEFAULT NULL,
                    reason VARCHAR(255) NOT NULL,
                    FOREIGN KEY(job_id) REFERENCES Job (id),
                    FOREIGN KEY(constraint_id) REFERENCES AccountPlanConstraint (id))",
                "CREATE UNIQUE INDEX UNIQ_F769EE08BE04EA9 ON JobRejectionReason (job_id)",
                "CREATE INDEX IDX_F769EE08E3087FFC ON JobRejectionReason (constraint_id)"
            ],
            'down' => [
                "DROP TABLE JobRejectionReason"
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