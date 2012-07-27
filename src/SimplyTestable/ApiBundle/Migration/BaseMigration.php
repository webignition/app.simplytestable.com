<?php
namespace SimplyTestable\ApiBundle\Migration;

use webignition\ContainerAwareMigration\ContainerAwareMigration;
use Doctrine\DBAL\Schema\Schema;

abstract class BaseMigration extends ContainerAwareMigration {
    
    private $databaseDrivers = array('mysql', 'sqlite');
    
    protected $statements = array();
    
    public function preUp(Schema $schema) {
        $this->abortIf(!in_array($this->connection->getDatabasePlatform()->getName(), $this->databaseDrivers), "Unknown database driver");
    }
    
    
    public function up(Schema $schema)
    {
        $this->execute();
    }
    
    
    public function down(Schema $schema)
    {
        $this->execute();
    }
    
    private function execute() {
        $this->checkStatementsExistForAllDatabaseDrivers();
        foreach ($this->statements[$this->connection->getDatabasePlatform()->getName()] as $statement) {
            $this->addSql($statement);
        }         
    }
    
    
    private function checkStatementsExistForAllDatabaseDrivers()
    {
        foreach ($this->databaseDrivers as $databaseDriver) {
            if (!isset($this->statements[$databaseDriver]) || !is_array($this->statements[$databaseDriver])) {
                $this->abortIf(true, "Statements missing for ".$databaseDriver." driver");
            }
        }        
    }
    
    

}