<?php
class Hymn_Command_Sleep extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run( $arguments = array() ){
		$seconds	= isset( $arguments[0] ) ? $arguments[0] : 1;
		$seconds	= max( 1, min( 10, abs( (int) $seconds ) ) );
		if( $seconds )
			sleep( $seconds );
	}
}
