<?php
class Hymn_Command_Reflect_Options extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		Hymn_Client::out();
		Hymn_Client::out( "DevMode: Reflects parsed argument options" );
		$this->client->arguments->registerOption( 'test', '/^(-t|--test)=(\S+)$/', '\\2' );
		$this->client->arguments->parse();
		print_r( $this->client->arguments->getOptions() );
	}
}
