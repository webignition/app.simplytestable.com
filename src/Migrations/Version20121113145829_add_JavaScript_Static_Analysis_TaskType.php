<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version20121113145829_add_JavaScript_Static_Analysis_TaskType extends AbstractMigration {

    public function up(Schema $schema) {
        $this->addSql("SELECT 1 + 1");
    }

    public function down(Schema $schema) {
        $this->addSql("SELECT 1 + 1");
    }

}