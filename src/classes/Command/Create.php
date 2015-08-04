<?php
class Hymn_Command_Create extends Hymn_Command_Abstract implements Hymn_Command_Interface{
	public function run( $arguments = array() ){
		$data	= array();
		Hymn_Client::out( "Please enter application information:" );
		$data['application']	= (object) array(
			'title'		=> Hymn_Client::getInput( "- Application title:", "My Hydrogen App" ),
			'protocol'	=> Hymn_Client::getInput( "- HTTP Protocol:", "http://" ),
			'host'		=> Hymn_Client::getInput( "- HTTP Host:", "localhost" ),
			'path'		=> Hymn_Client::getInput( "- HTTP Path:", "myApp" ),
			'uri'		=> Hymn_Client::getInput( " -Local Path:", "/var/www/myApp/" ),
		);

		$data['library']	= (object) array();
		$data['sources']	= (object) array();
		$data['modules']	= (object) array();

		Hymn_Client::out( "" );
		Hymn_Client::out( "Please enter database information:" );
		$data['database']	= (object) array(
			'driver'	=> Hymn_Client::getInput( "- PDO Driver:", "mysql" ),
			'host'		=> Hymn_Client::getInput( "- Host:", "localhost" ),
			'port'		=> Hymn_Client::getInput( "- Port:", "3306" ),
			'user'		=> Hymn_Client::getInput( "- Username:" ),
			'name'		=> Hymn_Client::getInput( "- Password:" ),
			'name'		=> Hymn_Client::getInput( "- Name:" ),
			'prefix'	=> Hymn_Client::getInput( "- Table Prefix:" ),
		);
		Hymn_Client::out( "" );
		Hymn_Client::out( "Please enter system information:" );
		$data['system']		= (object) array(
			'user'	=> Hymn_Client::getInput( "- System User:", get_current_user() ),
			'group'	=> Hymn_Client::getInput( "- System Group:", "www-data" ),
		);
		file_put_contents( ".hymn", json_encode( $data, JSON_PRETTY_PRINT ) );
		Hymn_Client::out( "Configuration file .hymn has been created." );
	}
}
