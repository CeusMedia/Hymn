<?php
class Hymn_Command_Help extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$config		= $this->client->getConfig();
		Hymn_Client::out();
		Hymn_Client::out( "Commands:" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "-- information --" );
		Hymn_Client::out( "- help                         Show this help screen" );
		Hymn_Client::out( "- info                         List application configuration" );
		Hymn_Client::out( "- sources                      List registered library shelves" );
		Hymn_Client::out( "- modules-available [SHELF]    List modules available in library shelve(s)" );
		Hymn_Client::out( "- modules-required             List modules required for application" );
		Hymn_Client::out( "- modules-installed            List modules installed within application" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "-- module management --" );
		Hymn_Client::out( "- install [MODULE]             Install modules of application or one specific" );
		Hymn_Client::out( "- uninstall [-f] MODULE        Uninstall one specifig installed module" );
		Hymn_Client::out( "- update [MODULE]              @dev" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "-- configuration --" );
		Hymn_Client::out( "- config-dump                  Export current module settings into Hymn file" );
		Hymn_Client::out( "- config-get KEY               Get setting from Hymn file" );
		Hymn_Client::out( "- config-set KEY [VALUE]       Enter and save setting in Hymn file" );
		Hymn_Client::out( "" );
		Hymn_Client::out( "-- database --" );
		Hymn_Client::out( "- database-clear [-f|-v|-q]    Drop database tables (force, verbose, quiet)" );
		Hymn_Client::out( "- database-config              Enter and save database connection details" );
		Hymn_Client::out( "- database-dump [PATH]         Export database to SQL file" );
		Hymn_Client::out( "- database-load [PATH|FILE]    Import (specific or latest) SQL file into database" );
		Hymn_Client::out( "- database-test                Test database connection" );
//		Hymn_Client::out( "- " );
  }
}
