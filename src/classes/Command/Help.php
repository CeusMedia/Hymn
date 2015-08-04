<?php
class Hymn_Command_Help extends Hymn_Command_Abstract implements Hymn_Command_Interface{
	public function run( $arguments = array() ){
		$config		= $this->client->getConfig();
		Hymn_Client::out();
		Hymn_Client::out( "Commands:" );
		Hymn_Client::out( "- help               Show this help screen" );
		Hymn_Client::out( "- info               List application configuration" );
		Hymn_Client::out( "- shelves            List registered library shelves" );
		Hymn_Client::out( "- install            Install modules of application" );
		Hymn_Client::out( "- configure          ..." );
		Hymn_Client::out( "- modules-required   List modules required for application" );
		Hymn_Client::out( "- modules-installed  List modules installed within application" );
//		Hymn_Client::out( "- " );
  }
}
