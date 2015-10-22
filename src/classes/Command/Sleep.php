<?php
class Hymn_Command_Sleep extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$seconds	= $this->client->arguments->getArgument();
		$seconds	= max( 1, min( 10, abs( (int) $seconds ) ) );
		if( $seconds )
			sleep( $seconds );
	}
}
