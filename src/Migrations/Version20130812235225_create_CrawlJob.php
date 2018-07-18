<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20130812235225_create_CrawlJob extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE CrawlJobContainer (
                    id INT AUTO_INCREMENT NOT NULL,
                    parent_job_id INT NOT NULL,
                    crawl_job_id INT NOT NULL,
                    UNIQUE INDEX UNIQ_7CB90CF4C04B9157 (crawl_job_id),
                    UNIQUE INDEX UNIQ_7CB90CF444F38D6F (parent_job_id),
                    PRIMARY KEY(id)
                 )DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB",
                "ALTER TABLE CrawlJobContainer ADD CONSTRAINT FK_7CB90CF4C04B9157 FOREIGN KEY (crawl_job_id) REFERENCES Job (id)",
                "ALTER TABLE CrawlJobContainer ADD CONSTRAINT FK_7CB90CF444F38D6F FOREIGN KEY (parent_job_id) REFERENCES Job (id)"
            ],
            'down' => [
                "DROP TABLE CrawlJobContainer"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE CrawlJobContainer (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    parent_job_id INT NOT NULL,
                    crawl_job_id INT NOT NULL,
                    FOREIGN KEY(crawl_job_id) REFERENCES Job (id),
                    FOREIGN KEY(parent_job_id) REFERENCES Job (id)
                 )",
                "CREATE UNIQUE INDEX UNIQ_7CB90CF4C04B9157 ON CrawlJobContainer (crawl_job_id)",
                "CREATE UNIQUE INDEX UNIQ_7CB90CF444F38D6F ON CrawlJobContainer (parent_job_id)"
            ],
            'down' => [
                "DROP TABLE CrawlJobContainer"
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