<?php
class Hymn_Command_Help extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){

		$action		= $this->client->arguments->getArgument( 0 );
		$className	= "Hymn_Command_Default";
		if( strlen( $action ) ){
			$command	= ucwords( preg_replace( "/-+/", " ", $action ) );
			$className	= "Hymn_Command_".preg_replace( "/ +/", "_", $command );
			if( !class_exists( $className ) )
				throw new InvalidArgumentException( 'Invalid action: '.$action );
			$class	= new ReflectionClass( $className );
			$object	= $class->newInstanceArgs( array( $this->client ) );
			call_user_func( array( $object, 'help' ) );
			return;
		}

		$config		= $this->client->getConfig();
		$lines		= file( "phar://hymn.phar/locales/en/help/default.txt" );
		Hymn_Client::out( $lines );
		return;
  }
}
