<?php
class Hymn_Command_Help extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run( $arguments = array() ){
		$config		= $this->client->getConfig();
		Hymn_Client::out();
		Hymn_Client::out( "Commands:" );
		Hymn_Client::out( "- help                         Show this help screen" );
		Hymn_Client::out( "- info                         List application configuration" );
		Hymn_Client::out( "- sources                      List registered library shelves" );
		Hymn_Client::out( "- install                      Install modules of application" );
		Hymn_Client::out( "- configure KEY [VALUE]        Enter and save settings in Hymn file" );
		Hymn_Client::out( "- configure-database           Enter and save database connection details" );
		Hymn_Client::out( "- configuration-dump           Export module settings to Hymn file." );
		Hymn_Client::out( "- modules-available [SHELF]    List modules available in library shelve(s)" );
		Hymn_Client::out( "- modules-required             List modules required for application" );
		Hymn_Client::out( "- modules-installed            List modules installed within application" );
		Hymn_Client::out( "- database-load [FILE]         Import SQL file into database" );
		Hymn_Client::out( "- database-test                Test database connection" );
		Hymn_Client::out( "- database-dump [PATH]         Export database to SQL file" );
		Hymn_Client::out( "- database-clear [-f|-v|-q]    Drop database tables (force, verbose, quiet)" );

//		Hymn_Client::out( "- " );
  }
}
