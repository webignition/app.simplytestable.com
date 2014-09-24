<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130425145822_create_AccountPlanConstraint extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE AccountPlanConstraint (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    limit_threshold INT DEFAULT NULL,
                    isAvailable TINYINT(1) NOT NULL,
                    plan_id INT NOT NULL,
                    UNIQUE INDEX UNIQ_E18FF0B75E237E06 (name),
                    INDEX IDX_E18FF0B7E3087FFC (plan_id),
                    PRIMARY KEY(id))
                    DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE AccountPlanConstraint ADD CONSTRAINT FK_E18FF0B7E3087FFC FOREIGN KEY (plan_id) REFERENCES AccountPlan (id)"
            ],
            'down' => [
                "DROP TABLE AccountPlanConstraint"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE AccountPlanConstraint (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    limit_threshold INT DEFAULT NULL,
                    isAvailable TINYINT(1) NOT NULL,
                    plan_id INT NOT NULL,
                    FOREIGN KEY(plan_id) REFERENCES AccountPlan (id))",
                "CREATE UNIQUE INDEX UNIQ_E18FF0B75E237E06 ON AccountPlanConstraint (name)",
                "CREATE INDEX IDX_E18FF0B7E3087FFC ON AccountPlanConstraint (plan_id)"
            ],
            'down' => [
                "DROP TABLE AccountPlanConstraint"
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