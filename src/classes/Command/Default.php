<?php
class Hymn_Command_Default extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		Hymn_Client::out( "Arguments:" );
		print_r( $this->client->arguments->getArguments() );
		Hymn_Client::out( "Options:" );
		print_r( $this->client->arguments->getOptions() );
	}
}
