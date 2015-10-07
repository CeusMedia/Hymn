<?php
class Hymn_Client{

	protected $application;

	protected $config;

	protected $instance;

	protected $dbc;

	protected $dba;

	static public $fileName	= ".hymn";

	static public $pathDefaults	= array(
		'images'		=> 'images/',
		'locales'		=> 'locales/',
		'scripts'		=> 'scripts/',
		'templates'		=> 'templates/',
		'themes'		=> 'themes/',
	);

	static public $version	= "0.7";

	public function __construct( $arguments ){
//		self::out( "Hymn Console Client" );
		ini_set( 'display_errors', TRUE );
		error_reporting( E_ALL );
		try{
			if( getEnv( 'HTTP_HOST' ) )
				throw new RuntimeException( 'Access denied' );
			$action	= isset( $arguments[0] ) ? $arguments[0] : NULL;
			if( !in_array( $action, array( 'help', 'create', 'version' ) ) ){
				$this->readConfig();
				$this->loadLibraries();
//				$this->setupDatabaseConnection();
			}
			$this->dispatch( $arguments );
			self::out();
		}
		catch( Exception $e ){
			self::out( "Error: ".$e->getMessage() );
		}
	}

	protected function dispatch( $arguments ){
		$argument		= @array_shift( @array_values( $arguments ) );
		$className	= "Hymn_Command_Default";
		if( strlen( $argument ) ){
			$command		= ucwords( preg_replace( "/-+/", " ", $argument ) );
			$className	= "Hymn_Command_".preg_replace( "/ +/", "", $command );
			if( !class_exists( $className ) )
				throw new InvalidArgumentException( 'Invalid action: '.$argument );
		}
//		self::out( "Command Class: ".$className );
		try{
			$object			= new $className( $this );
			$object->run( $arguments );
		}
		catch( Exception $e ){
			Hymn_Client::out( $e->getMessage() );
			exit;
		}
	}

	public function getConfig(){
		return $this->config;
	}

	public function getDatabase(){
		return $this->dbc;
	}

	public function getDatabaseConfiguration( $key = NULL ){
		if( is_null( $key ) )
			return $this->dba;
		if( isset( $this->dba->$key ) )
			return $this->dba->$key;
		else
			throw new InvalidArgumentException( 'Invalid database access property key "'.$key.'"' );
	}

	static public function getInput( $message, $default = NULL, $options = array(), $break = TRUE ){
		if( strlen( trim( $default ) ) )
			$message	.= " [".$default."]";
		if( is_array( $options ) && count( $options ) )
			$message	.= " (".implode( "|", $options ).")";
		if( !$break )
			$message	.= ": ";
		do{
			Hymn_Client::out( $message, $break );
			$handle	= fopen( "php://stdin","r" );
			$line		= trim( fgets( $handle ) );
			if( !strlen( $line ) && $default )
				$line	= $default;
		}
		while( $options && !in_array( $line, $options ) );
		return $line;
	}

	public function getModuleConfiguration( $moduleId, $key = NULL, $force = FALSE ){
		if( !isset( $this->config->modules->$moduleId ) ){
			if( $force )
				throw new RuntimeException( 'Configuration of module "'.$moduleId.'" is needed but missing' );
			return (object) array();
		}
		$config	= $this->config->modules->{$moduleId}->config;
		if( !is_null( $key ) ){
			if( isset( $config->{$key} ) )
				return $config->{$key};
			if( $force )
				throw new RuntimeException( 'Configuration of module "'.$moduleId.'" has no property "'.$key.'"' );
			return NULL;
		}
		return $config;
	}

	public function getModuleInstallType( $moduleId, $defaultInstallType = "copy" ){
		$type	= $defaultInstallType;
		if( isset( $this->config->modules->{"@installType"} ) )
			$type	= $this->config->modules->{"@installType"};
		if( isset( $this->config->modules->$moduleId ) )
			if( isset( $this->config->modules->$moduleId->{"installType"} ) )
				$type	= $this->config->modules->$moduleId->{"installType"};
		return $type;
	}

	protected function loadLibraries(){
		foreach( $this->config->library as $library ){
			if( !@include_once $library.'autoload.php5' )
				throw new RuntimeException( 'Missing cmClasses in "'.$this->config->library->cmClasses.'"' );

		}
//		if( !@include_once $this->config->library->cmClasses.'autoload.php5' )
//			throw new RuntimeException( 'Missing cmClasses in "'.$this->config->library->cmClasses.'"' );
//		if( !@include_once $this->config->library->cmFrameworks.'autoload.php5' )
//			throw new RuntimeException( 'Missing cmFrameworks in "'.$this->config->library->cmFrameworks.'"' );
	}

	static public function out( $message = NULL, $newLine = TRUE ){
		print( $message );
		if( $newLine )
			print( PHP_EOL );
	}

	protected function readConfig( $filename = NULL ){
		$filename	= $filename ? $filename : self::$fileName;
		if( !file_exists( $filename ) )
			throw new RuntimeException( 'File "'.$filename.'" is missing' );
		$this->config	= json_decode( file_get_contents( $filename ) );
		if( is_null( $this->config ) )
			throw new RuntimeException( 'Configuration file "'.$filename.'" is not valid JSON' );
		if( is_string( $this->config->sources ) ){
			if( !file_exists( $this->config->sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is missing' );
			$sources	= json_decode( file_get_contents( $this->config->sources ) );
			if( is_null( $sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is not valid JSON' );
			$this->config->sources = $sources;
		}
		$this->config->paths	= (object) array();
		foreach( self::$pathDefaults as $pathKey => $pathValue )
			if( !isset( $this->config->paths->{$pathKey} ) )
				$this->config->paths->{$pathKey}	= $pathValue;

		if( file_exists( 'config/config.ini' ) ){
			$data	= parse_ini_file( 'config/config.ini' );
			foreach( $data as $key => $value ){
				if( preg_match( "/^path\./", $key ) ){
					$key	= preg_replace( "/^path\./", "", $key );
					$key	= ucwords( str_replace( ".", " ", $key ) );
					$key	= str_replace( " ", "", lcfirst( $key ) );
					$this->config->paths->{$key}	= $value;
				}
				else{
					$key	= ucwords( str_replace( ".", " ", $key ) );
					$key	= str_replace( " ", "", lcfirst( $key ) );
					$this->config->{$key}	= $value;
				}
			}
		}
	}

	public function setupDatabaseConnection( $force = FALSE ){
		if( $this->dbc )
			return;
//		$this->dbc			= NULL;
		$usesGlobalDbAccess	= isset( $this->config->database ) && $this->config->database;
		$usesDatabaseModule	= isset( $this->config->modules->Resource_Database->config );
		if( $usesGlobalDbAccess ){
			$this->dba		= $this->config->database;
		}
		else if( $usesDatabaseModule ){
			$config		= array();
			foreach( $this->config->modules->Resource_Database->config as $key => $value )
				$config[preg_replace("/^access\./", "", $key)]	= $value;
			$this->dba	= (object) $config;
		}

		if( empty( $this->dba ) ){
			if( $force )
				throw new RuntimeException( 'Database access needed but not configured' );
			return;
		}
		$this->dba->driver		= isset( $this->dba->driver ) ? $this->dba->driver : "mysql";
		$this->dba->host		= isset( $this->dba->host ) ? $this->dba->host : "localhost";
		$this->dba->port		= isset( $this->dba->port ) ? $this->dba->port : "3306";
		$this->dba->name		= isset( $this->dba->name ) ? $this->dba->name : NULL;
		$this->dba->prefix		= isset( $this->dba->prefix ) ? $this->dba->prefix : NULL;
		$this->dba->username	= isset( $this->dba->username ) ? $this->dba->username : NULL;
		$this->dba->password	= isset( $this->dba->password ) ? $this->dba->password : NULL;

		if( !in_array( $this->dba->driver, PDO::getAvailableDrivers() ) ){
			throw new RuntimeException( 'PDO driver "'.$this->dba->driver.'" is not available' );
		}
		while( empty( $this->dba->name ) ){
			$this->dba->name		= Hymn_Client::getInput( "Database Name:" );
		}
		while( empty( $this->dba->username ) ){
			$this->dba->username	= Hymn_Client::getInput( "Database Username:" );
		}
		while( empty( $this->dba->password ) ){
			$this->dba->password	= Hymn_Client::getInput( "Database Password:" );
		}
		while( is_null( $this->dba->prefix ) ){
			$this->dba->prefix		= Hymn_Client::getInput( "Table Prefix:" );
		}
		$dsn			= $this->dba->driver.":host=".$this->dba->host.";port=".$this->dba->port.";dbname=".$this->dba->name;
		$this->dbc		= new PDO( $dsn, $this->dba->username, $this->dba->password );
		if( !isset( $this->config->modules->Resource_Database ) )
			$this->config->modules->Resource_Database	= (object) array();
		$this->config->modules->Resource_Database->config	= (object) array();
		foreach( $this->dba as $key => $value )
			$this->config->modules->Resource_Database->config->{"access.".$key}	= $value;
	}
}
?>
