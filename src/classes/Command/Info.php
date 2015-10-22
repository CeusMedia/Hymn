<?php
class Hymn_Command_Info extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$config		= $this->client->getConfig();
		Hymn_Client::out( "Application Settings:" );
		foreach( $config->application as $key => $value ){
			if( is_object( $value ) )
				$value	= json_encode( $value, JSON_PRETTY_PRINT );
			Hymn_Client::out( "- ".$key." => ".$value );
		}
	}
}
