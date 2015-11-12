<?php

/*
    To create your migration class - 
    
    run Phinx create command with your version number instead of NNN in the Migration folder
        
        ../../../vendor/bin/phinx create SmartcellarDbMigrationVersionNNN --template="SmartcellarDbMigrationTemplate.php"    
        
        Example - To Create Version 001 - 
        ../../../vendor/bin/phinx create SmartcellarDbMigrationVersion001 --template="SmartcellarDbMigrationTemplate.php"     
    
    The class SmartcellarDbMigrationVersion001.php will be created in the migrations folder with the name:
            phinxdate_smartcellar_db_migration_version001.php            
    
     1.  rename this class to match the name of the migration you created.  You should just be updating the NNN to the correct number.
     
     2.  Updated the following class properties:
                dbVersionNumber - NNN - should match the version number in the class name.
                versionDescription  - 1 line description of update.
                developmentNumber   - Jira number, not required.
                updateType - PRODUCT or CLIENT
                dbClientArray (only if CLIENT update) - set to client databases that your changes should be installed in.
                
     3.  Put your migrate up code in migrateUp()
     
     4.  Put your migrate down (rollback) code in migrateDown()

    5.  Test locally using SmartcellarDBUpgradeScript.php
    
    6.  Push to git ticket branch in Migrations/migrations folder.
         
*/

use Phinx\Migration\AbstractMigration;
require_once 'SmartcellarMigrationClasses.php';

class SmartcellarDbMigrationVersionNNN extends AbstractMigration
{
    
    private $dbVersionNumber='NNN';   
    private $versionDescription="PUT BRIEF DESCRIPTION OF VERSION/UPDATE HERE";
    private $developmentNumber="";   //This should be the ticket number - if you want to add it.
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
       
    }

/* Function processMigration
    Description:  This function is called from up & down functions to initialize migration, call migrateUp or migrateDown, and
                        finalize migration.  This should not be changed.
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
