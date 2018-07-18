<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150302211459_create_JobConfiguration_and_JobTaskConfiguration extends AbstractMigration
{
    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE JobConfiguration (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    website_id INT NOT NULL,
                    type_id INT NOT NULL,
                    `label` VARCHAR(255) NOT NULL,
                    parameters LONGTEXT DEFAULT NULL,
                    INDEX IDX_549B62D9A76ED395 (user_id),
                    INDEX IDX_549B62D918F45C82 (website_id),
                    INDEX IDX_549B62D9C54C8C93 (type_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "CREATE TABLE JobTaskConfiguration (
                    id INT AUTO_INCREMENT NOT NULL,
                    jobconfiguration_id INT NOT NULL,
                    type_id INT DEFAULT NULL,
                    options LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
                    INDEX IDX_C42E5F65E5B4855B (jobconfiguration_id),
                    INDEX IDX_C42E5F65C54C8C93 (type_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE JobConfiguration ADD CONSTRAINT FK_549B62D9A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
                "ALTER TABLE JobConfiguration ADD CONSTRAINT FK_549B62D918F45C82 FOREIGN KEY (website_id) REFERENCES WebSite (id)",
                "ALTER TABLE JobConfiguration ADD CONSTRAINT FK_549B62D9C54C8C93 FOREIGN KEY (type_id) REFERENCES JobType (id)",
                "ALTER TABLE JobTaskConfiguration ADD CONSTRAINT FK_C42E5F65E5B4855B FOREIGN KEY (jobconfiguration_id) REFERENCES JobConfiguration (id)",
                "ALTER TABLE JobTaskConfiguration ADD CONSTRAINT FK_C42E5F65C54C8C93 FOREIGN KEY (type_id) REFERENCES TaskType (id)"
            ],
            'down' => [
                "ALTER TABLE JobTaskConfiguration DROP FOREIGN KEY FK_C42E5F65E5B4855B",
                "DROP TABLE JobConfiguration",
                "DROP TABLE JobTaskConfiguration"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE JobConfiguration (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    website_id INT NOT NULL,
                    type_id INT NOT NULL,
                    `label` VARCHAR(255) NOT NULL,
                    parameters LONGTEXT DEFAULT NULL,
                    FOREIGN KEY(user_id) REFERENCES fos_user (id),
                    FOREIGN KEY(website_id) REFERENCES WebSite (id),
                    FOREIGN KEY(type_id) REFERENCES JobType (id)
                )",
                "CREATE INDEX IDX_549B62D9A76ED395 ON JobConfiguration (user_id)",
                "CREATE INDEX IDX_549B62D918F45C82 ON JobConfiguration (website_id)",
                "CREATE INDEX IDX_549B62D9C54C8C93 ON JobConfiguration (type_id)",
                "CREATE TABLE JobTaskConfiguration (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    jobconfiguration_id INT NOT NULL,
                    type_id INT DEFAULT NULL,
                    options LONGTEXT NOT NULL,
                    FOREIGN KEY(jobconfiguration_id) REFERENCES JobConfiguration (id),
                    FOREIGN KEY(type_id) REFERENCES TaskType (id)
                )",
                "CREATE INDEX IDX_C42E5F65E5B4855B ON JobTaskConfiguration (jobconfiguration_id)",
                "CREATE INDEX IDX_C42E5F65C54C8C93 ON JobTaskConfiguration (type_id)"
            ],
            'down' => [
                "ALTER TABLE JobTaskConfiguration DROP FOREIGN KEY FK_C42E5F65E5B4855B",
                "DROP TABLE JobConfiguration",
                "DROP TABLE JobTaskConfiguration"
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
