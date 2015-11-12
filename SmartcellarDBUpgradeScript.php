
<?php

/* Revision History

    SMC-410 9/2015 - Initial Version.
    
*/

/* this script is used for database versioning for smartcellar/smarttouch systems */

/*  This is called from git post_merge hook to upgrade the database when needed.   
    The hook is located in .git/hooks, and runs automatically whenever a pull is done to production.
    
     Input Command Line Parameters:  migrationType - if set to rollback - a rollback will be done.  If set to migrate, all migrations up to date will be done,  if set to status, only status will be displayed.
                                                                                    Status will be displayed automatically after a rollback or migration.
                                                         smartcellarDatabase - if entered, this smartcellar database will be the only database migration executed.  when not entered, the migration will be executed for
                                                                                         all smartcellar databases on the server.

     This script does the following:
     
     1.  Initializes database connection information into environment variables for phinx to access thru setup in phinx.yml file.  The .htaccess file is parsed to get database connection info.
          When being run for all databases, the first smartcellar_*_client/.htaccess file is used to get database connection information.  
          When run for a specific smartcellar database, smartcellarDB_client/.htaccess is used for connection info.
     
     2.  executes phinx migration for all or selected database (based on input parameter).  After migration is executed, a status is always executed.                 
     
  */

    $migrationType='';
    $smartcellarDB="";
    
    if (isset($argv[1])) {
        $migrationType = $argv[1];
    }
    
    if ($migrationType !== 'status' && $migrationType !== 'migrate' && $migrationType !== 'rollback') {
        echo "******* ERROR: Invalid input parameter - migration type:  $migrationType, valid entries are 'status', 'migrate', or 'rollback'".PHP_EOL;
        return;
    }
    
    if (isset($argv[2])) {
        $smartcellarDB = $argv[2];
    }
    
    $smartcellarDBArray=setupPhinxEnvironmentVariables($smartcellarDB);    
    
    //setting to production - reading database info from .htaccess - so it will grab whatever is actually running.
    $environment="production";
    
    if (count($smartcellarDBArray)) {
        foreach ($smartcellarDBArray as $dbName) {
            //setup phinx environment variable for the database name
            putenv("PHINX_DBNAME=".$dbName);
        
            if ($migrationType !== 'status') {
                executePhinxMigrations($migrationType,$environment);  
                echo "Database $migrationType executed for $dbName".PHP_EOL; 
            }      
        
            echo "Database Migration Current Status for $dbName ".PHP_EOL;  
            executePhinxMigrations("status",$environment);         
        }
    }
    else {
        echo ' *****  no DBs to update';
    }

   
/* ---------- Functions below support migrations         --------------*/

    
    /* Function:  setupPhinxEnvironmentVariables
        Description:  Sets up environment variables by parsing .htaccess file.
          sets host, username, password from .htaccess located in /var/www/smartcellar_*client.
        Parameters:  smartcellar database name.  If set, then that client/database .htaccess file is used.
        Returns:  dbsOnServer  - array of smartcellar databases on server that will have migrations run.   
    */ 
        
function setupPhinxEnvironmentVariables($smartcellarDB='') 
{
    
   $htAccessFolder="/var/www/smartcellar/";
 
    if (file_exists($htAccessFolder) === false) {
        $htAccessFolder="/var/www/";  
    }
    
    
    if ($smartcellarDB !== '') {
        $dbsOnServer=array($smartcellarDB);    
        $primaryFolder=$smartcellarDB."_client";
    }
    else {
        $dbsOnServer=array();
        $smartcellarFolders = scandir($htAccessFolder);
        $primaryFolder=getPrimarySmartcellarFolder($smartcellarFolders);
    }
    
    //read in .htaccess file.
    $htAccessFile = file($htAccessFolder.$primaryFolder.'/.htaccess', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); 

    $envValues=array();
    
    //these are based for egloo environment variables set in .htaccess.
    $envVariablesArray=array( "EG_DB_CONNECTION_PRIMARY_HOST",
                                                "EG_DB_CONNECTION_PRIMARY_USER",
                                                "EG_DB_CONNECTION_PRIMARY_PASSWORD");
                                                
    //initialize envValues array to blanks - based on envVariablesArray.
    foreach ($envVariablesArray as $index=>$val) {
        $envValues[$val]='';    
    }
    
    foreach ($htAccessFile As $index=>$line) {
        //process lines that aren't commented outl, and include setenv.        
        if (strpos($line,"#") === false && (strpos($line,"SetEnv")) !== false) {
            //find db parameter functions above.
            foreach ($envVariablesArray As $index=>$envVar) {
                if (strpos($line,$envVar) !== false) {
                    $envValues[$envVar] = getEnvVariables($line,$envVar);             
                }           
            }                 
        }    
    }
    
    putenv("PHINX_DBHOST=".$envValues['EG_DB_CONNECTION_PRIMARY_HOST']);
    putenv("PHINX_DBUSER=".$envValues["EG_DB_CONNECTION_PRIMARY_USER"]);
    putenv("PHINX_DBPASS=".$envValues["EG_DB_CONNECTION_PRIMARY_PASSWORD"]);
    putenv("PHINX_CONFIG_DIR=migrations");

    // if smartcellar db was not passed in, then return array with all smartcellar dbs on the server.   
    if ($smartcellarDB === '') {
        $dbsOnServer=getDBs($envValues['EG_DB_CONNECTION_PRIMARY_HOST'], $envValues['EG_DB_CONNECTION_PRIMARY_USER'],$envValues['EG_DB_CONNECTION_PRIMARY_PASSWORD'],"smartcellar_%");
    }
    
    return $dbsOnServer;
}

/* function getDBs
    Desctription:  Gets the MGM databases to run migrations on.  It selects databases from the server based on pattern sent in.
    Returns:  array of smartcellar databases.
*/

function getDBs($host,$user,$pw,$pattern)
{
    $returnArray=array();
    
    $pdo = new PDO( "mysql:host=".$host.";", $user,$pw);	
   
     //select all databases like pattern sent in.
    $sql="SELECT SCHEMA_NAME as dbName FROM INFORMATION_SCHEMA.SCHEMATA where SCHEMA_NAME like '".$pattern."'";
    $resultArray=$pdo->query($sql);
    
    if ($resultArray !== false) {
        print_r($resultArray);
        if (count($resultArray)) {
            foreach ($resultArray as $row) {
                $returnArray[]=$row['dbName'];            
            }
        }
    }    	
    
    //close connection.
    $pdo=null;
    
    	return $returnArray;
}

/*
    Function:  getEnvVariables
    Description:  Returns the environment variable from a line in the .htaccess file.
    Parameters:  line - line from htaccess
                        envVar - environment variables to return value of.
    Returns:  The value of the environment variable from the line passed in.
*/
function getEnvVariables($line,$envVar)
{
    $returnVal='';
    //remove all blanks from the string.
    $line=str_replace(' ','',$line);
    $tempArray=explode($envVar,$line);
    //index 1 of array should have value - if it is set.   
    if (isset($tempArray[1])) {
        $returnVal=$tempArray[1];    
    }
    
    return $returnVal;
}

/*
    Function:  getPrimarySmartcellarFolder
    Description:  returns the smartcellar folder that will be used to get the database connect info from the .htaccess file.
    Parameters:  smartcellarArray - array of folders in /www/var/smartcellar.  
    Returns:  first smartcellar_*_client folder that has an .htaccess file in it.
*/

function getPrimarySmartcellarFolder($smartcellarArray)
{

    $primaryFolder='';
    $pattern1="smartcellar_";
    $pattern2="_client";
    
    $totalArrayItems=count($smartcellarArray);
    $cnt=0;
    
    while (($cnt < $totalArrayItems) && ($primaryFolder ==='')) {
        $folder=$smartcellarArray[$cnt];
         if ((strpos($folder,$pattern1) !== false) && (strpos($folder,$pattern2) !== false))  {
            $primaryFolder=$folder;        
        }
        $cnt++;
    }
    
    echo "Database Connection Info Defaulted From:  $primaryFolder".PHP_EOL;
    
    return $primaryFolder;

}

/* Function:  executePhinxMigrations
    Description:  executes the phinx command based on input parameters.
    Parameters:  command:  migrate, rollback, status
                        environment:  can be any environment in phinx.yml file, but for this application it is always production.
    Returns:  N/A.  Outputs result of phinx command.
    
    *** path to phinx command might need to be adjusted based on where it will be installed on production servers.

*/

function executePhinxMigrations($command, $environment) 
{
    $path="../../../vendor/bin/phinx";
    $cmd="$path $command -e $environment";
    
    $outArray=array();
    $retValue='';
    
    exec($cmd,$outArray,$retValue);
    print_r($outArray);
}
  
?>
