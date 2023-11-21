<?php
class Hymn_Tool_Database_PDO
{
	protected Hymn_Client $client;
	protected $dba;
	protected $dbc;

	protected $dbaDefaults	= [
		'driver'		=> 'mysql',
		'host'			=> 'localhost',
		'port'			=> '3306',
		'name'			=> NULL,
		'prefix'		=> NULL,
		'username'		=> NULL,
		'password'		=> NULL,
		'modules'		=> '',
	];

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		Hymn_Client		$client		Hymn client instance
	 *	@return		void
	 */
	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	/**
	 *	Applied table prefix to SQL with table prefix placeholders.
	 *	@access		public
	 *	@param		string		$sql		SQL with prefix placeholders
	 *	@param		string|NULL	$prefix		Table prefix to apply, default: none (empty)
	 *	@return		string		SQL with applied table prefix
	 */
	public function applyTablePrefixToSql( string $sql, ?string $prefix = NULL ): string
	{
		$prefix		= $prefix ? $prefix : $this->getConfig( 'prefix' );								//  use given or configured table prefix
		return str_replace( "<%?prefix%>", $prefix, $sql );											//  apply table prefix to SQL and return result
	}

	/**
	 *	Establishes database connection.
	 *	@access		public
	 *	@param		boolean		$force			Flag: ...
	 *	@param		boolean		$forceReset		Flag: ...
	 *	@return		void
	 *	@todo		implment force or remove (this is the way to go since dba has been extracted to prepareConnection)
	 */
	public function connect( bool $force = FALSE, bool $forceReset = FALSE )
	{
		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;
		if( $this->dbc && !$forceReset )
			return;

		$this->prepareConnection( TRUE, $forceReset );
		if( !in_array( $this->dba->driver, PDO::getAvailableDrivers() ) ){
			$this->client->outError( 'PDO driver "'.$this->dba->driver.'" is not available', Hymn_Client::EXIT_ON_SETUP );
		}
		while( empty( $this->dba->name ) ){
			$this->dba->name		= $this->ask( 'Database Name:' );
		}
		while( empty( $this->dba->username ) ){
			$this->dba->username	= $this->ask( 'Database Username:' );
		}
		while( empty( $this->dba->password ) ){
			$this->dba->password	= $this->ask( 'Database Password:' );
		}
		while( is_null( $this->dba->prefix ) ){
			$this->dba->prefix		= $this->ask( 'Table Prefix:' );
		}

		if( strtolower( $this->dba->driver ) !== 'mysql' )											//  exclude other PDO drivers than 'mysql' @todo improve this until v1.0!
			$this->client->outError( vsprintf( 'PDO driver "%s" is not supported at the moment', [
				$this->dba->driver
			] ), Hymn_Client::EXIT_ON_SETUP );
		$dsn			= $this->dba->driver.':'.implode( ';', [
			'host='.$this->dba->host,
			'port='.$this->dba->port,
//			'dbname='.$this->dba->name,
		] );
		if( $this->dbc && $forceReset )
			unset( $this->dbc );
		$this->client->outVerbose( 'Connecting database ... ', FALSE );
		$this->dbc		= new PDO( $dsn, $this->dba->username, $this->dba->password );
		$this->client->outVerbose( 'OK' );
		$this->dbc->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		$this->dbc->query( 'SET CHARSET utf8' );
		try{
			if( !$this->dbc->query( 'SHOW DATABASES LIKE "'.$this->dba->name.'"' )->fetch() ){
				$this->client->outVerbose( 'Creating database "'.$this->dba->name.'" ...', FALSE );
				$this->dbc->query( 'CREATE DATABASE `'.$this->dba->name.'`;' );
				$this->client->outVerbose( 'OK' );
			}
			if( $this->dbc->query( 'SHOW DATABASES LIKE "'.$this->dba->name.'"' )->fetch() ){
				$this->client->outVerbose( 'Switching into database "'.$this->dba->name.'" ...', FALSE );
				$this->dbc->query( 'USE `'.$this->dba->name.'`;' );
				$this->client->outVerbose( 'OK' );
			}
		}
		catch( Exception $e ){
			$this->client->outError( 'SQL setup failed: '.$e->getMessage() );
		}
	}

	/**
	 *	Wraps PDO::exec in a lazy mode.
	 *	Connects database if not done before..
	 *	@access		public
	 *	@param		string		$statement		Statement to execute
	 *	@return		integer
	 *	@see		http://php.net/manual/en/pdo.exec.php
	 */
	public function exec( string $statement )
	{
		$this->connect();
		return $this->dbc->exec( $statement );
	}

	/**
	 *	Returns database access configuration as object or a single pair by given key.
	 *	@access		public
	 *	@param		string		$key		Key to return single pair for (optional)
	 *	@return		object|string
	 *	@throws		DomainException			if key is not set in configuration
	 */
	public function getConfig( string $key = NULL )
	{
		$this->prepareConnection( FALSE, FALSE );
		if( !$this->dba )
			$this->client->outError( 'Database support is not configured (on getConfig).', Hymn_Client::EXIT_ON_SETUP );
		if( is_null( $key ) )
			return $this->dba;
		if( isset( $this->dba->$key ) )
			return $this->dba->$key;
		else
			throw new DomainException( 'Invalid database access property key "'.$key.'"' );
	}

	/**
	 *	Indicates whether a database connection has been established.
	 *	@access		public
	 *	@return		boolean
	 */
	public function isConnected(): bool
	{
		return (bool) $this->dbc;
	}

	/**
	 *	Returns list of tables within database.
	 *	With given prefix, the returned list of tables will be filtered.
	 *	@access		public
	 *	@param		string		$prefix		Table prefix (optional)
	 *	@return		array
	 */
	public function getTables( ?string $prefix = NULL ): array
	{
		$query		= "SHOW TABLES" . ( $prefix ? " LIKE '".$prefix."%'" : "" );
		$result		= $this->query( $query );
		return $result->fetchAll( PDO::FETCH_COLUMN );
	}

	/**
	 *	Wraps PDO::query in a lazy mode.
	 *	Connects database if not done before.
	 *	@access		public
	 *	@param		string		$query			Query to run
	 *	@return		PDOStatement
	 *	@see		http://php.net/manual/en/pdo.query.php
	 */
	public function query( string $query ): PDOStatement
	{
		$this->connect();
		return $this->dbc->query( $query );
	}

	/*  --  PROTECTED  --  */

	protected function ask( string $message, string $type = 'string', $default = NULL, array $options = [], bool $break = TRUE ): string
	{
		$question	= new Hymn_Tool_CLI_Question(
			$this->client,
			$message,
			$type,
			$default,
			$options,
			$break
		);
		return $question->ask();
	}

	/**
	 *	Prepare database connection by setting up database access configuration.
	 *	No database connection will be established.
	 *	Will lookout for 3 configuration sources:
	 *	- global: hymn file configuration
	 *	- linked: database resource modules linked in hymn file (in database.modules)
	 *	- default: pseudo default database resource module Resource_Database from CeusMedia:HydrogenModules to be installed
	 *
	 *	In most cases, the hymn file will already (or still) hold database access information.
	 *
	 *	Secondly, the hymn file allows to link database resource modules.
	 *	So, database access information can be retrived from one of these modules if installed.
	 *
	 *	As a fallback the pseudo-default database resource module Resource_Database will be looked after.
	 *
 	 *	Using force mode, having no configuration will lead to abortion.
	 *	Using force reset mode will read configuration again ignoring beforehand preparation.
	 *
	 *	@access		public
	 *	@param		boolean		$force			Flag: throw exception if no database configuration available (default: yes)
	 *	@param		boolean		$reset			Flag: read configuration again ignoring beforehand preparation (default: no)
	 *	@return		void
	 */
	protected function prepareConnection( bool $force = TRUE, bool $reset = FALSE )
	{
		if( $this->dba && !$reset )																	//  connection access already prepared and not forced to reset
			return;																					//  do nothing
		$config				= $this->client->getConfig();											//  shortcut configuration from hymn file
		$usesGlobalDbAccess	= !empty( $config->database->name );									//  atleast the database name is defined in hymn file
		$usesLinkedModules	= !empty( $config->database->modules );									//  database resource modules are are linked in hymn file
		$usesDefaultModule	= isset( $config->modules->Resource_Database->config );					//  pseudo-default resource module from CeusMedia:HydrogenModules is installed
		if( $usesGlobalDbAccess ){																	//  use global database access information from hymn file first for better performance
			$configAsArray	= (array) $config->database;											//  convert config object to array
			$this->dba		= (object) array_merge( $this->dbaDefaults, $configAsArray );			//  set database access information from hymn file config
		}
		else if( $usesDefaultModule ){																//  use the pseudo-default resource module first for better performance
			$this->dba	= (object) $this->dbaDefaults;												//  prepare database access information using defaults as template
			foreach( $config->modules->Resource_Database->config as $key => $value )				//  iterate config pairs of installed database resource module
				if( preg_match( '/^access\./', $key ) )												//  config key prefix is matching
					$this->dba->{preg_replace( '/^access\./', '', $key )}	= $value;				//  carry resource module config value to database access information
		}
		else if( $usesLinkedModules ){
			$parts		= preg_split( '/\s*,\s*/', $config->database->modules );					//  split comma separated list if resource modules in registration format
			foreach( $parts as $moduleRegistration ){												//  iterate these module registrations
				$moduleId		= $moduleRegistration;												//  assume module ID to be the while module registration string ...
				$configPrefix	= '';																//  ... and no config prefix as fallback (simplest situation)
				if( preg_match( '/:/', $moduleRegistration ) )										//  a prefix defintion has been announced
					list( $moduleId, $configPrefix ) = preg_split( '/:/', $moduleRegistration, 2 );	//  split module ID and config prefix into variables
				if( !isset( $config->modules->{$moduleId} ) )										//  linked resource module is NOT installed
					continue;																		//  skip this module registration
				$this->dba		= (object) $this->dbaDefaults;										//  prepare database access information using defaults as template
				$quotedPrefix	= preg_quote( $configPrefix, '/' );									//  quote resource module config key prefix prefix for preg operations
				foreach( $config->modules->{$moduleId}->config as $key => $value ){					//  iterate config pairs of installed database resource module
					if( $quotedPrefix ){															//  a resource module config key prefix has been registered
						if( !preg_match( '/^'.$quotedPrefix.'/', $key ) )							//  current module config key is not of registered prefix
							continue;
						$key	= preg_replace( '/^'.$quotedPrefix.'/', '', $key );					//  otherwise remove prefix from config key
					}
					$this->dba->{$key}	= $value;													//  carry resource module config value to database access information
				}
				break;																				//  stop after first success
			}
		}
		if( !$this->dba && $force ){
			$this->client->outError(
				'Database access needed but not configured',
				Hymn_Client::EXIT_ON_SETUP
			);
		}
	}
}
