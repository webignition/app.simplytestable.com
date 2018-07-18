<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120724125613_add_TaskTypeClasses extends AbstractMigration {

    public function up(Schema $schema) {
        $this->addSql("SELECT 1 + 1");
    }

    public function down(Schema $schema) {
        $this->addSql("SELECT 1 + 1");
    }

}
