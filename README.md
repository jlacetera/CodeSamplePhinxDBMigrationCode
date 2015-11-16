# CodeSamplePhinxDBMigrationCode

Incentient's Smartcellar product did not use any tools or automated process to deploy database changes to the core product, or client specific database changes (typically installing custom client data).  The QA manager would log into each client server and copy/paste sql commands that were provided by a developer.  So as the client base grew, this process is not reasonable or acceptable.  This was one of those 'ya gotta be kidding me' moments, when I learned that this is how it was done.
So I was given the task of coming up with a solution (which I gladly took on, because this had to be fixed).
For a good description on how this is implemented using phinx - see the comments in SmartcellarDBUpgradeScripts.php (which runs on git hook), and SmartcellarDbMigrationTemplate.php.

