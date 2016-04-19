<?php
class Hymn_Command_Help extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$config		= $this->client->getConfig();
		Hymn_Client::out();
		Hymn_Client::out( "Commands:" );
		Hymn_Client::out( "----------" );
		Hymn_Client::out( "1. information" );
		Hymn_Client::out( "- help                         Show this help screen" );
		Hymn_Client::out( "- info                         List application configuration" );
		Hymn_Client::out( "- sources                      List registered library shelves" );
		Hymn_Client::out( "- modules-available [SHELF]    List modules available in library shelve(s)" );
		Hymn_Client::out( "- modules-required             List modules required for application" );
		Hymn_Client::out( "- modules-installed            List modules installed within application" );
		Hymn_Client::out( "- modules-updatable            List modules with available updates" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "2. Module Management" );
		Hymn_Client::out( "- install [-dqv] [MODULE]      Install modules (or one specific)" );
		Hymn_Client::out( "- uninstall [-dfqv] MODULE     Uninstall one specific installed module" );
		Hymn_Client::out( "- update [-dqv] [MODULE]       Updated installed modules (or one specific)" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "3. Module Configuration" );
		Hymn_Client::out( "- config-dump                  Export current module settings into Hymn file" );
		Hymn_Client::out( "- config-get KEY               Get setting from Hymn file" );
		Hymn_Client::out( "- config-set KEY [VALUE]       Enter and save setting in Hymn file" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "4. Base Configuration" );
		Hymn_Client::out( "- config-base-disable KEY      Disable an enabled setting in config.ini" );
		Hymn_Client::out( "- config-base-enable KEY       Enable a disabled setting in config.ini" );
		Hymn_Client::out( "- config-base-get KEY          Read setting from config.ini" );
		Hymn_Client::out( "- config-base-set KEY VALUE    Save setting in config.ini" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "5. Database" );
		Hymn_Client::out( "- database-clear [-fqv]        Drop database tables (force, verbose, quiet)" );
		Hymn_Client::out( "- database-config              Enter and save database connection details" );
		Hymn_Client::out( "- database-dump [PATH]         Export database to SQL file" );
		Hymn_Client::out( "- database-load [PATH|FILE]    Import (specific or latest) SQL file" );
		Hymn_Client::out( "- database-test                Test database connection" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "Options:" );
		Hymn_Client::out( "---------" );
		Hymn_Client::out( "-d | --dry                     Actions are simulated only" );
		Hymn_Client::out( "-f | --force                   Continue actions on error or warnings" );
		Hymn_Client::out( "-q | --quiet                   Avoid any output" );
		Hymn_Client::out( "-v | --verbose                 Be verbose about taken steps" );
		Hymn_Client::out( "--file=[.hymn]                 Alternative path of Hymn file" );
		Hymn_Client::out( "" );
//		Hymn_Client::out( "- " );
//		Hymn_Client::out( "- " );
  }
}
