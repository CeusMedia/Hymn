<?php
class Hymn_Command_Test extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$this->client->arguments->registerOption( 'recursive', '/^-r|--recursive$/', TRUE );
		$this->client->arguments->parse();

		$this->recursive	= $this->client->arguments->getOption( 'recursive' );
		$this->quiet		= $this->client->arguments->getOption( 'quiet' );
		$this->verbose		= $this->client->arguments->getOption( 'verbose' );

		$path	= $this->client->arguments->getArgument( 0 );
		if( !$path )
			$path	= ".";

		Hymn_Test::checkPhpClasses( $path, $this->recursive, $this->verbose );

/*		Hymn_Test::checkPhpClasses( "./", FALSE, !FALSE );
		if( file_exists( "classes" ) )
			Hymn_Test::checkPhpClasses( "./classes", TRUE, !FALSE );
		if( file_exists( "templates" ) )
			Hymn_Test::checkPhpClasses( "./templates", TRUE, !FALSE );
*/	}
}
