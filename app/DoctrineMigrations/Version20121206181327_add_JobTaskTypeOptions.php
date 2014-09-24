<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20121206181327_add_JobTaskTypeOptions extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE JobTaskTypeOptions (
                    id INT AUTO_INCREMENT NOT NULL,
                    job_id INT NOT NULL,
                    tasktype_id INT NOT NULL,
                    options LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
                    INDEX UNIQ_A72BF044BE04EA9 (job_id),
                    INDEX IDX_A72BF0447D6EFC3 (tasktype_id),
                    PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE JobTaskTypeOptions ADD CONSTRAINT FK_A72BF044BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)",
                "ALTER TABLE JobTaskTypeOptions ADD CONSTRAINT FK_A72BF0447D6EFC3 FOREIGN KEY (tasktype_id) REFERENCES TaskType (id)"
            ],
            'down' => [
                "DROP TABLE JobTaskTypeOptions"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE JobTaskTypeOptions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    job_id INT NOT NULL,
                    tasktype_id INT NOT NULL,
                    options LONGTEXT NOT NULL,
                    FOREIGN KEY (job_id) REFERENCES Job (id),
                    FOREIGN KEY (tasktype_id) REFERENCES TaskType (id))",
                "ALTER TABLE Job ADD taskTypeOptions INT DEFAULT NULL",
                "CREATE INDEX UNIQ_A72BF044BE04EA9 ON JobTaskTypeOptions (job_id)",
                "CREATE INDEX IDX_A72BF0447D6EFC3 ON JobTaskTypeOptions (tasktype_id)"
            ],
            'down' => [
                "DROP TABLE JobTaskTypeOptions"
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