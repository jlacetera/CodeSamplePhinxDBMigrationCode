<?php

/* Revision History

    SMC-420 - JL  9/2015 - initial version.

*/
/*
    This source file contains the following classes:
        SmartcellarMigration
        MigrationUtilities
*/

/*
    Class:  SmartcellarMigration
    Description:  This class has functions that are called to support phinx migrations.  The migration template, which is used to create the
                        migration classes that phinx calls uses the functions and logic in thie class.
                        
*/
class SmartcellarMigration
{

    //these are setup in the Migration class, and initialized here in the constructor.    
    private $dbVersionNumber;   //This is the version number of the migraton - 001 - NNN.
    private $versionDescription;  //This is brief description of the update/version.
    private $developmentNumber;   //This should be the ticket number - if you want to add it.
    private $updateType;   //this should be CLIENT or PRODUCT - based on whether this is a client specific change, or general product change.
    private $dbClientArray=array();    //This is an array of client database names that the client migration should get executed for.
    private $pdo;
    
    public $migrationUtils;  //pointer to MigrationUtilities class, calling class will have access to this.  Initialized in constructor.

function __construct($pdo,$dbVersionNumber='',$versionDescription='',$developmentNumber='',$updateType='PRODUCT',$dbClientArray=array())
{
    $this->migrationUtils=new MigrationUtilities();
    
    //set class properties based on parameters sent in.    
    $this->pdo=$pdo;
    $this->dbVersionNumber=$dbVersionNumber;
    $this->versionDescription=$versionDescription;
    $this->developmentNumber=$developmentNumber;
    $this->updateType=$updateType;
    $this->dbClientArray=$dbClientArray;
 
}
    
    /*
        Function:  updateDBVersionFile
        Description:  updates the database_version file with this migration information based on class properties.
        Parameters:  dbName - if passed as a parameter, use command is executing to select the database.
        Return:  N/A.
            
    */
    private function updateDBVersionFile() 
    {
        
        if ($this->migrationUtils->tableExists("database_version",$this->pdo)) {
            $sql = $this->pdo->prepare("INSERT INTO database_version (current_version,update_status,update_type, version_description,development_number) VALUES (?, ?, ?, ?, ?)");       
            $sql->execute(array($this->dbVersionNumber, $this->updateStatus,$this->updateType,$this->versionDescription,$this->developmentNumber));              
        }    
    }
    
    /*
        Function:  rollbackDBVersionFile
        Description:  called on a migration down/rollback, to remove the entry in the database_version table for this version.
        Parameters:  none
        Return:  N/A   
    */
    private function rollbackDBVersionFile()   
    {
        if ($this->migrationUtils->tableExists("database_version",$this->pdo)) {
            $count=$this->pdo->exec("delete from database_version where current_version = '$this->dbVersionNumber'");    
        }    
    }
    

 /*
    Function endMigration
    Description:  called at the end of all migrations to update database version file, and do any other cleanup that might be required.
                        This is called from migration class, so it is public.
    Parameters:  type - up/down
                        status - SUCCESS,FAIL,SUCCESS_CLIENT_SKIPPED.  It updates updateStatus if set.
    Returns:  N/A
 */   
  public function endMigration($type,$status='')
   {
       if ($status !== '') {
            $this->updateStatus=$status;       
       }
        switch ($type) {
            case 'up':
                $this->updateDBVersionFile();
                break;
            case 'down':
                $this->rollbackDBVersionFile();
                break;
            default:
                //this should never happen
                break;        
        
        }
   }
   
   /*
        Function:  updateThisClient
        Description:  based on current database and databases in dbClientArray,  determine whether or not to install client changes.
                            this is called from migration class - so it is public.
        Parameters:  none
        Returns:  true/false.   True - install changes for this client, false to skip this client. 
   */
  public function updateThisClient()
  {
     $returnVal=false; 
     $clientDB=null;
     
   //get current database
    $sql="SELECT DATABASE() as db;";
    $resultArray=$this->pdo->query($sql);
    if ($resultArray !== false) {
        if (count($resultArray)) {
            foreach ($resultArray as $row) {
                $clientDB=$row['db'];   
            }
        }
    }
    
    if ($clientDB !== null) {
        if (in_array($clientDB, $this->dbClientArray)) {
            $returnVal=true;    
        }
    }
    
   return $returnVal;
  }  
  }

/*
    Class:  MigrationUtilities
    Description:  Utility functions that can be used by migration classes.  These are public functions that can be used/called by passing in
                        the required parameters.

*/
class MigrationUtilities    
 {
 /*   
        Function:  tableExists
        Description:  checks if table exists.
        Parameters:  tableName, pdo connection.
        Returns:  true if table exists, false if table doesn't exist.  
  */
  public function tableExists($tableName,$pdo)
  {    
    $returnVal=false; 
    $sql="SHOW TABLES LIKE '".$tableName."'";
    
    $resultArray=$pdo->query($sql);
    $count=0;
    
    //For some reason, a 'count' of this array does not necessary indicate the number of rows returned ??
    //resultArray is a PDO object.
    foreach ($resultArray As $ind=>$val) {
        $count++;    
    }
    
    if ($count>0) {
        $returnVal=true;    
    }
  
    return $returnVal;
  }        

}


?>