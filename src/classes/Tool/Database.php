<?php
class Hymn_Tool_Database{

	protected $client;
	protected $dba;
	protected $dbc;

	protected $dbaDefaults	= array(
		'driver'		=> 'mysql',
		'host'			=> 'localhost',
		'port'			=> '3306',
		'name'			=> NULL,
		'prefix'		=> NULL,
		'username'		=> NULL,
		'password'		=> NULL,
		'modules'		=> '',
	);

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		Hymn_Client		$client		Hymn client instance
	 *	@return		void
	 */
	public function __construct( Hymn_Client $client ){
		$this->client	= $client;
	}

	/**
	 *	Applied table prefix to SQL with table prefix placeholders.
	 *	@access		public
	 *	@param		string		$sql		SQL with prefix placeholders
	 *	@param		string		$prefix		Table prefix to apply, default: none (empty)
	 *	@return		string		SQL with applied table prefix
	 */
	public function applyTablePrefixToSql( $sql, $prefix = NULL ){
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
	public function connect( $force = FALSE, $forceReset = FALSE ){
		if( $this->client->flags & Hymn_Client::FLAG_NO_DB )
			return;
		if( $this->dbc && !$forceReset )
			return;

		$this->prepareConnection( TRUE, $forceReset );
		if( !in_array( $this->dba->driver, PDO::getAvailableDrivers() ) ){
			$this->client->outError( 'PDO driver "'.$this->dba->driver.'" is not available', Hymn_Client::EXIT_ON_SETUP );
		}
		while( empty( $this->dba->name ) ){
			$this->dba->name		= $this->client->ask( 'Database Name:' );
		}
		while( empty( $this->dba->username ) ){
			$this->dba->username	= $this->client->ask( 'Database Username:' );
		}
		while( empty( $this->dba->password ) ){
			$this->dba->password	= $this->client->ask( 'Database Password:' );
		}
		while( is_null( $this->dba->prefix ) ){
			$this->dba->prefix		= $this->client->ask( 'Table Prefix:' );
		}

		if( strtolower( $this->dba->driver ) !== 'mysql' )											//  exclude other PDO drivers than 'mysql' @todo improve this until v1.0!
			$this->client->outError( vsprintf( 'PDO driver "%s" is not supported at the moment', array(
				$this->dba->driver
			) ), Hymn_Client::EXIT_ON_SETUP );
		$dsn			= $this->dba->driver.':'.implode( ';', array(
			'host='.$this->dba->host,
			'port='.$this->dba->port,
//			'dbname='.$this->dba->name,
		) );
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
	public function exec( $statement ){
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
	public function getConfig( $key = NULL ){
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
	public function isConnected(){
		return (bool) $this->dbc;
	}

	/**
	 *	Returns list of tables within database.
	 *	With given prefix, the returned list of tables will be filtered.
	 *	@access		public
	 *	@param		string		$prefix		Table prefix (optional)
	 *	@return		array
	 */
	public function getTables( $prefix = NULL ){
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
	public function query( $query ){
		$this->connect();
		return $this->dbc->query( $query );
	}

	/*  --  PROTECTED  --  */

	/**
	 *	Prepare database connection by setting up database access configuration.
	 *	No database connection will be established.
	 *	Will lookout for module Resource_Database and hymn configuration.
	 *	Having a setup in hymn file means to have a "global configuration".
	 *	Having module Resource_Database installed means to have a "module configuration".
	 *	If both are missing, nothing will be done.
 	 *	Using force mode, having no configuration will lead to abortion.
	 *	Using force reset mode will read configuration again ignoring beforehand preparation.
	 *
	 *	@access		public
	 *	@param		boolean		$force			Flag: throw exception if no database configuration available (default: yes)
	 *	@param		boolean		$forceReset		Flag: read configuration again ignoring beforehand preparation (default: no)
	 *	@return		void
	 */
	protected function prepareConnection( $force = TRUE, $reset = FALSE ){
		if( $this->dba && !$reset )
			return;
		$config				= $this->client->getConfig();
		$usesGlobalDbAccess	= isset( $config->database ) && $config->database;
		$usesDatabaseModule	= isset( $config->modules->Resource_Database->config );
		if( $usesGlobalDbAccess && !empty( $config->database->name ) ){
			$this->dba		= (object) array_merge( $this->dbaDefaults, (array) $config->database );
		}
		else if( $usesDatabaseModule ){
			$this->dba	= (object) $this->dbaDefaults;
			foreach( $config->modules->Resource_Database->config as $key => $value )
				if( preg_match( '/^access\./', $key ) )
					$this->dba->{preg_replace( '/^access\./', '', $key )}	= $value;
		}
		if( !$this->dba && $force ){
			$this->client->outError( 'Database access needed but not configured', Hymn_Client::EXIT_ON_SETUP );
		}
	}
}
