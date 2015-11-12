<?php

use Phinx\Migration\AbstractMigration;
require_once 'SmartcellarMigrationClasses.php';

class SmartcellarDbMigrationVersion001 extends AbstractMigration
{
    
    private $dbVersionNumber='001';   
    private $versionDescription="Initial version, installs database_version table";
    private $developmentNumber="SMC-420";   //This should be the ticket number - if you want to add it.
    private $updateType='PRODUCT';   //this should be CLIENT or PRODUCT - based on whether this is a client specific change, or general product change.
    private $dbClientArray=array();    //for client updates, this is an array of databases that will get updated.  For product updates, this should remain empty.
 
    //*** example of set up for CLIENT update.
    //private $updateType='CLIENT';   //this should be CLIENT or PRODUCT - based on whether this is a client specific change, or general product change.
    //private $dbClientArray=array("smartcellar_balisea","smartcellar_circo");    //for client updates, this is an array of databases that will get updated.  For product updates, this should remain empty.
   
 
    private $migration;                 //class pointer to SmartcellarMigration class
    private $pdo;                           //pdo  connection to the database that is being updated.  This is needed to execute any queries.
    private $migrationStatus='SUCCESS';  //status of migration, saved  with migration database.  SUCCESS, FAIL, or SUCCESS_CLIENT_SKIPPED.     
    private $migrationType;         //this is set to up or down based on migration type.
 
 
    /*
    Function:  migrateUp
    Description:  This is where your migration up code should be placed.  
        migrate up code for client and product migrations go in this function.  If this is a client migration, this function
        will only be called if the database that is currently selected is defined in $this->dbClientArray;
    
        You have access to the following:
        $this->pdo - which is needed to run any database queries.
        $this->migration->migrationUtils - which gives you access to any utility functions in MigrationUtilities class.
        
         $this->migrationStatus is set to SUCCESS prior to calling this function.  If there is an error, your code should
            set $this->migrationStatus="FAIL"
    
    */   
    
    private function migrateUp()
    {
          if ($this->migration->migrationUtils->tableExists('database_version',$this->pdo) === false) {
            $sql="CREATE TABLE `database_version` (
                        id int(11) unsigned NOT NULL AUTO_INCREMENT,
                        current_version varchar(100) DEFAULT NULL,
                        update_status varchar(100) DEFAULT NULL,
                        update_type varchar(100) DEFAULT NULL,
                        version_description varchar(500) DEFAULT NULL,
                        development_number varchar(100) DEFAULT NULL,  
                        update_date_time timestamp DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
             
             $this->pdo->exec($sql);
        }
        
        //reset $this->migrationStatus to FAIL if needed, it is already initializeed to SUCCESS. 
    
    }
    
    /*
    Function:  migrateDown
    Description:  This is where your migration down code should be placed.  
        migrate down code for client and product migrations go in this function.  If this is a client migration, this function
        will only be called if the database that is currently selected is defined in $this->dbClientArray;
    
        You have access to the following:
        $this->pdo - which is needed to run any database queries.
        $this->migration->migrationUtils - which gives you access to any utility functions in MigrationUtilities class.
    
    */   
    
    private function migrateDown()
    {
        if ($this->migration->migrationUtils->tableExists('database_version',$this->pdo) === true) {
            $this->pdo->exec("DROP TABLE database_version");
        } 
       
    }

/* Function processMigration
    Description:  This function is called from up & down functions to initialize migration, call migrateUp or migrateDown, and
                        finalize migration.
    Parameters:  none
    Returns:  none
*/

    private function processMigration()
    {
        $this->setPDO();
        $this->migration=new SmartcellarMigration($this->pdo,$this->dbVersionNumber,$this->versionDescription,$this->developmentNumber,$this->updateType,$this->dbClientArray);
        $execMigration=true;
        $this->migrationStatus='SUCCESS';
        
        if (($this->updateType === 'CLIENT') && ($this->migration->updateThisClient() === false)) {
            $execMigration=false;
            $this->migrationStatus='SUCCESS_CLIENT_SKIPPED';        
        }
        
        if ($execMigration === true) {
            if ($this->migrationType === 'up') {
                $this->migrateUp();            
            }
            else {
                $this->migrateDown();
            }    
        }
        
        $this->endMigration();
    
    } 
    
    /*
        Function: up
        Description:  This is the function that is called from phinx migration when an up migration (migrate) is executed.
                            It calls processMigration to handle all of the migration logic for smartcellar.
    */
        
    public function up()
    {
        $this->migrationType='up';
        $this->processMigration();
    }
    
    /*
        Function:  down
        Description: This is the function that is called from a phinx migration when a down migration (rollback) is executed.
                            It calls processMigration to handle all of the migration logic for smartcellar.
     */    
    
    public function down()
    {
        $this->migrationType='down';
        $this->processMigration();
    }
        
    
    /*
        Function:  endMigration
        Description:  This is called when the migration logic for migrate or rollback is completed.  It calls endMigration in SmartcellarMigration class
                            to update the appropriate smartcellar tables with the migration info.
    */
    private function endMigration() 
    {
        $this->migration->endMigration($this->migrationType,$this->migrationStatus);
    }
    
    /*
        Function:  setPDO
        Description:  this function sets pdo connection pointer from phinx adapter so that class funcions can execute pdo commands directly.
    */
    private function setPDO()
    {
        $this->pdo=null;
        $dbAdapter = $this->getAdapter();

        if ($dbAdapter instanceof \Phinx\Db\Adapter\PdoAdapter) {
           $this->pdo = $dbAdapter->getConnection();
        }
    
    }
}


?>
