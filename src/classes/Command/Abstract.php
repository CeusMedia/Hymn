<?php
abstract class Hymn_Command_Abstract{

	public function __construct( Hymn_Client $client ){
		$this->client = $client;
	}

	public function help(){
		$class		= preg_replace( "/^Hymn_Command_/", "", get_class( $this ) );
		$command	= strtolower( str_replace( "_", "-", $class ) );
		$fileName	= "phar://hymn.phar/locales/en/help/".$command.".txt";
		if( file_exists( $fileName ) )
			Hymn_Client::out( file( $fileName ) );
		else{
			Hymn_Client::out();
			Hymn_Client::out( "Outch! Help on this topic is not available yet. I am sorry :-/" );
			Hymn_Client::out();
			Hymn_Client::out( "But YOU can improve this situation :-)" );
			Hymn_Client::out( "- get more information on: https://ceusmedia.de/" );
			Hymn_Client::out( "- make a fork or patch on: https://github.com/CeusMedia/" );
			Hymn_Client::out();
		}
	}
}
