<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120716214708_create_Job extends AbstractMigration {

    private $statements = [
        'mysql' => [
            'up' => [
                "CREATE TABLE Job (
                    id INT AUTO_INCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    website_id INT NOT NULL,
                    state_id INT NOT NULL,
                    timePeriod_id INT DEFAULT NULL,
                    INDEX IDX_C395A618A76ED395 (user_id),
                    INDEX IDX_C395A61818F45C82 (website_id),
                    INDEX IDX_C395A6185D83CC1 (state_id),
                    UNIQUE INDEX UNIQ_C395A618E43FFED1 (timePeriod_id),
                    PRIMARY KEY(id)) ENGINE = InnoDB",
                "ALTER TABLE Job ADD CONSTRAINT FK_C395A618A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)",
                "ALTER TABLE Job ADD CONSTRAINT FK_C395A61818F45C82 FOREIGN KEY (website_id) REFERENCES WebSite (id)",
                "ALTER TABLE Job ADD CONSTRAINT FK_C395A6185D83CC1 FOREIGN KEY (state_id) REFERENCES State (id)",
                "ALTER TABLE Job ADD CONSTRAINT FK_C395A618E43FFED1 FOREIGN KEY (timePeriod_id) REFERENCES TimePeriod (id)"
            ],
            'down' => [
                "DROP TABLE Job"
            ]
        ],
        'sqlite' => [
            'up' => [
                "CREATE TABLE Job (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    user_id INT NOT NULL,
                    website_id INT NOT NULL,
                    state_id INT NOT NULL,
                    timePeriod_id INT DEFAULT NULL,
                    FOREIGN KEY(user_id) REFERENCES fos_user (id),
                    FOREIGN KEY(website_id) REFERENCES WebSite (id),
                    FOREIGN KEY(state_id) REFERENCES State (id),
                    FOREIGN KEY(timePeriod_id) REFERENCES TimePeriod (id))",
                "CREATE INDEX IDX_C395A618A76ED395 ON Job (user_id)",
                "CREATE INDEX IDX_C395A61818F45C82 ON Job (website_id)",
                "CREATE INDEX IDX_C395A6185D83CC1 ON Job (state_id)",
                "CREATE UNIQUE INDEX UNIQ_C395A618E43FFED1 ON Job (timePeriod_id)"
            ],
            'down' => [
                "DROP TABLE Job"
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