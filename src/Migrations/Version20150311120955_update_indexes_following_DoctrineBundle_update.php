<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150311120955_update_indexes_following_DoctrineBundle_update extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE CrawlJobContainer DROP FOREIGN KEY FK_7CB90CF444F38D6F');
        $this->addSql('ALTER TABLE CrawlJobContainer DROP FOREIGN KEY FK_7CB90CF4C04B9157');
        $this->addSql('DROP INDEX uniq_7cb90cf4c04b9157 ON CrawlJobContainer');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10EB2EA8C04B9157 ON CrawlJobContainer (crawl_job_id)');
        $this->addSql('DROP INDEX uniq_7cb90cf444f38d6f ON CrawlJobContainer');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_10EB2EA844F38D6F ON CrawlJobContainer (parent_job_id)');
        $this->addSql('ALTER TABLE CrawlJobContainer ADD CONSTRAINT FK_7CB90CF444F38D6F FOREIGN KEY (parent_job_id) REFERENCES Job (id)');
        $this->addSql('ALTER TABLE CrawlJobContainer ADD CONSTRAINT FK_7CB90CF4C04B9157 FOREIGN KEY (crawl_job_id) REFERENCES Job (id)');
        $this->addSql('ALTER TABLE WorkerActivationRequest DROP FOREIGN KEY FK_57FF325218F45C82');
        $this->addSql('DROP INDEX uniq_57ff325218f45c82 ON WorkerActivationRequest');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57FF32526B20BA36 ON WorkerActivationRequest (worker_id)');
        $this->addSql('ALTER TABLE WorkerActivationRequest ADD CONSTRAINT FK_57FF325218F45C82 FOREIGN KEY (worker_id) REFERENCES Worker (id)');
        $this->addSql('ALTER TABLE Team DROP FOREIGN KEY FK_64D20921A76ED395');
        $this->addSql('DROP INDEX idx_64d20921a76ed395 ON Team');
        $this->addSql('CREATE INDEX IDX_64D2092173154ED4 ON Team (leader_id)');
        $this->addSql('ALTER TABLE Team ADD CONSTRAINT FK_64D20921A76ED395 FOREIGN KEY (leader_id) REFERENCES fos_user (id)');
        $this->addSql('ALTER TABLE JobTaskTypeOptions DROP FOREIGN KEY FK_A72BF044BE04EA9');
        $this->addSql('DROP INDEX uniq_a72bf044be04ea9 ON JobTaskTypeOptions');
        $this->addSql('CREATE INDEX IDX_A72BF044BE04EA9 ON JobTaskTypeOptions (job_id)');
        $this->addSql('ALTER TABLE JobTaskTypeOptions ADD CONSTRAINT FK_A72BF044BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)');
        $this->addSql('ALTER TABLE AccountPlanConstraint DROP FOREIGN KEY FK_E18FF0B7E3087FFC');
        $this->addSql('DROP INDEX idx_e18ff0b7e3087ffc ON AccountPlanConstraint');
        $this->addSql('CREATE INDEX IDX_E18FF0B7E899029B ON AccountPlanConstraint (plan_id)');
        $this->addSql('ALTER TABLE AccountPlanConstraint ADD CONSTRAINT FK_E18FF0B7E3087FFC FOREIGN KEY (plan_id) REFERENCES AccountPlan (id)');
        $this->addSql('DROP INDEX uniq_981eba54f47645ae ON Worker');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_981EBA54E551C011 ON Worker (hostname)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE AccountPlanConstraint DROP FOREIGN KEY FK_E18FF0B7E899029B');
        $this->addSql('DROP INDEX idx_e18ff0b7e899029b ON AccountPlanConstraint');
        $this->addSql('CREATE INDEX IDX_E18FF0B7E3087FFC ON AccountPlanConstraint (plan_id)');
        $this->addSql('ALTER TABLE AccountPlanConstraint ADD CONSTRAINT FK_E18FF0B7E899029B FOREIGN KEY (plan_id) REFERENCES AccountPlan (id)');
        $this->addSql('ALTER TABLE CrawlJobContainer DROP FOREIGN KEY FK_10EB2EA8C04B9157');
        $this->addSql('ALTER TABLE CrawlJobContainer DROP FOREIGN KEY FK_10EB2EA844F38D6F');
        $this->addSql('DROP INDEX uniq_10eb2ea8c04b9157 ON CrawlJobContainer');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7CB90CF4C04B9157 ON CrawlJobContainer (crawl_job_id)');
        $this->addSql('DROP INDEX uniq_10eb2ea844f38d6f ON CrawlJobContainer');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7CB90CF444F38D6F ON CrawlJobContainer (parent_job_id)');
        $this->addSql('ALTER TABLE CrawlJobContainer ADD CONSTRAINT FK_10EB2EA8C04B9157 FOREIGN KEY (crawl_job_id) REFERENCES Job (id)');
        $this->addSql('ALTER TABLE CrawlJobContainer ADD CONSTRAINT FK_10EB2EA844F38D6F FOREIGN KEY (parent_job_id) REFERENCES Job (id)');
        $this->addSql('ALTER TABLE JobTaskTypeOptions DROP FOREIGN KEY FK_A72BF044BE04EA9');
        $this->addSql('DROP INDEX idx_a72bf044be04ea9 ON JobTaskTypeOptions');
        $this->addSql('CREATE INDEX UNIQ_A72BF044BE04EA9 ON JobTaskTypeOptions (job_id)');
        $this->addSql('ALTER TABLE JobTaskTypeOptions ADD CONSTRAINT FK_A72BF044BE04EA9 FOREIGN KEY (job_id) REFERENCES Job (id)');
        $this->addSql('ALTER TABLE Team DROP FOREIGN KEY FK_64D2092173154ED4');
        $this->addSql('DROP INDEX idx_64d2092173154ed4 ON Team');
        $this->addSql('CREATE INDEX IDX_64D20921A76ED395 ON Team (leader_id)');
        $this->addSql('ALTER TABLE Team ADD CONSTRAINT FK_64D2092173154ED4 FOREIGN KEY (leader_id) REFERENCES fos_user (id)');
        $this->addSql('DROP INDEX uniq_981eba54e551c011 ON Worker');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_981EBA54F47645AE ON Worker (hostname)');
        $this->addSql('ALTER TABLE WorkerActivationRequest DROP FOREIGN KEY FK_57FF32526B20BA36');
        $this->addSql('DROP INDEX uniq_57ff32526b20ba36 ON WorkerActivationRequest');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57FF325218F45C82 ON WorkerActivationRequest (worker_id)');
        $this->addSql('ALTER TABLE WorkerActivationRequest ADD CONSTRAINT FK_57FF32526B20BA36 FOREIGN KEY (worker_id) REFERENCES Worker (id)');
    }
}
