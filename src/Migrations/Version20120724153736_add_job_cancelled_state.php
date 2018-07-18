<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20120724153736_add_job_cancelled_state extends AbstractMigration {

    public function up(Schema $schema) {
        $this->addSql("SELECT 1 + 1");
    }

    public function down(Schema $schema) {
        $this->addSql("SELECT 1 + 1");
    }

}