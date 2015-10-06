<?php
class Hymn_Command_Create extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run( $arguments = array() ){
		$data	= array();
//		Hymn_Client::out();
		Hymn_Client::out( "Please enter application information:" );
		$data['application']	= (object) array(
			'title'		=> Hymn_Client::getInput( "- Application title", "My Hydrogen App", NULL, FALSE ),
			'protocol'	=> Hymn_Client::getInput( "- HTTP Protocol", "http://", NULL, FALSE ),
			'host'		=> Hymn_Client::getInput( "- HTTP Host", "localhost", NULL, FALSE ),
			'path'		=> Hymn_Client::getInput( "- HTTP Path", "myApp", NULL, FALSE ),
			'uri'		=> Hymn_Client::getInput( " -Local Path", "/var/www/myApp/", NULL, FALSE ),
		);

		$data['library']	= (object) array();
		$data['sources']	= (object) array();
		$data['modules']	= (object) array();

		Hymn_Client::out( "" );
		Hymn_Client::out( "Please enter database information:" );
		$data['database']	= (object) array(
			'driver'	=> Hymn_Client::getInput( "- PDO Driver", "mysql", NULL, FALSE ),
			'host'		=> Hymn_Client::getInput( "- Host", "localhost", NULL, FALSE ),
			'port'		=> Hymn_Client::getInput( "- Port", "3306", NULL, FALSE ),
			'username'	=> Hymn_Client::getInput( "- Username", NULL, NULL, FALSE ),
			'password'	=> Hymn_Client::getInput( "- Password", NULL, NULL, FALSE ),
			'name'		=> Hymn_Client::getInput( "- Name", NULL, NULL, FALSE ),
			'prefix'	=> Hymn_Client::getInput( "- Table Prefix", NULL, NULL, FALSE ),
		);
		Hymn_Client::out( "" );
		Hymn_Client::out( "Please enter system information:" );
		$data['system']		= (object) array(
			'user'	=> Hymn_Client::getInput( "- System User", get_current_user(), NULL, FALSE ),
			'group'	=> Hymn_Client::getInput( "- System Group", "www-data", NULL, FALSE ),
		);
		file_put_contents( Hymn_Client::$fileName, json_encode( $data, JSON_PRETTY_PRINT ) );
		Hymn_Client::out( "Configuration file ".Hymn_Client::$fileName." has been created." );
	}
}
