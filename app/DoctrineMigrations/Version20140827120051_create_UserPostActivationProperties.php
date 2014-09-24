<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20140827120051_create_UserPostActivationProperties extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE UserPostActivationProperties (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    accountplan_id INT NOT NULL,
                    coupon VARCHAR(255) DEFAULT NULL,
                    INDEX IDX_B1AF6847A76ED395 (user_id),
                    INDEX IDX_B1AF6847369E2B6B (accountplan_id),
                    PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE UserPostActivationProperties ADD CONSTRAINT FK_B1AF6847A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
                "ALTER TABLE UserPostActivationProperties ADD CONSTRAINT FK_B1AF6847369E2B6B FOREIGN KEY (accountplan_id) REFERENCES AccountPlan (id)"
            ],
            'down' => [
                "DROP TABLE UserPostActivationProperties"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE UserPostActivationProperties (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    accountplan_id INT NOT NULL,
                    coupon VARCHAR(255) DEFAULT NULL,
                    FOREIGN KEY(user_id) REFERENCES fos_user (id),
                    FOREIGN KEY(accountplan_id) REFERENCES AccountPlan (id)
                )",
                "CREATE INDEX IDX_B1AF6847A76ED395 ON UserPostActivationProperties (user_id)",
                "CREATE INDEX IDX_B1AF6847369E2B6B ON UserPostActivationProperties (accountplan_id)",
            ],
            'down' => [
                "DROP TABLE UserPostActivationProperties"
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