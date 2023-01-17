<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2022 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Tool_CLI_Config
{
	static public array $pathDefaults	= [
		'config'		=> 'config/',
		'classes'		=> 'classes/',
		'images'		=> 'images/',
		'locales'		=> 'locales/',
		'scripts'		=> 'scripts/',
		'templates'		=> 'templates/',
		'themes'		=> 'themes/',
	];

	public bool $isLiveCopy			= FALSE;

	protected Hymn_Client $client;

	protected ?object $config		= NULL;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		Hymn_Client		$client			Instance of Hymn client
	 *	@return		void
	 */
	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	public function readConfig()
	{
		if( !file_exists( Hymn_Client::$fileName ) )
			throw new RuntimeException( 'File "'.Hymn_Client::$fileName.'" is missing. Please use command "init"' );
		$this->config	= json_decode( file_get_contents( Hymn_Client::$fileName ) );
		if( is_null( $this->config ) )
			throw new RuntimeException( 'Configuration file "'.Hymn_Client::$fileName.'" is not valid JSON' );

/*		if( is_string( $this->config->sources ) ){
			if( !file_exists( $this->config->sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is missing' );
			$sources	= json_decode( file_get_contents( $this->config->sources ) );
			if( is_null( $sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is not valid JSON' );
			$this->config->sources = $sources;
		}*/

		$app	= $this->config->application;
		if( isset( $app->configPath ) )
			self::$pathDefaults['config'] = $app->configPath;

		$this->applyBaseConfiguredPathsToAppConfig();
		$this->applyAppConfiguredDatabaseConfigToModules();
		if( isset( $app->installMode ) && $app->installMode === 'live' )							//  is live installation
			if( isset( $app->installType ) && $app->installType === 'copy' )						//  is installation copy
				$this->isLiveCopy = TRUE;															//  this installation is a build for a live copy
		return $this->config;
	}

	/*  --  PROTECTED  --  */

	/**
	 *	Copies database access information into database related resource modules.
	 *	Such modules can be registered in hymn file in 'database.modules' as string separated list of resource modules and option key prefixes (optional).
	 *	Example: Resource_Database:access.,MyDatabaseResourceModule:myOptionPrefix.
	 *	Hint: Hence the tailing dot.
	 *	Default module to use is Resource_Database, if no other definition has been found.
	 *
	 *	@access		protected
	 *	@param		array			$modules		Map of resource modules with config option key prefix (eG. Resource_Database:access.).
	 *	@return		bool
	 *	@todo		kriss: Question is: Why? On which purpose is this important, again?
	 */
	protected function applyAppConfiguredDatabaseConfigToModules( array $modules = [] ): bool
	{
		if( !isset( $this->config->database ) )
			return FALSE;

		if( !$modules ){																			//  no map of resource modules with config option key prefix given on function call
			$modules	= [];																	//  set empty map
			if( !isset( $this->config->database->modules ) )										//  no database resource modules defined in hymn file (default)
				$this->config->database->modules	= 'Resource_Database:access.';					//  set atleast pseudo-default resource module from CeusMedia:HydrogenModules
			$parts	= preg_split( '/\s*,\s*/', $this->config->database->modules );					//  split comma separated list if resource modules in registration format
			foreach( $parts as $moduleRegistration ){												//  iterate these module registrations
				$moduleId		= $moduleRegistration;												//  assume module ID to be the while module registration string ...
				$configPrefix	= '';																//  ... and no config prefix as fallback (simplest situation)
				if( preg_match( '/:/', $moduleRegistration ) )										//  a prefix defintion has been announced
					list( $moduleId, $configPrefix ) = preg_split( '/:/', $moduleRegistration, 2 );	//  split module ID and config prefix into variables
				$modules[$moduleId]	= $configPrefix;												//  enlist resource module registration in array form
			}
		}
		foreach( $modules as $moduleId => $configPrefix ){											//  iterate given or found resource module registrations
		//	$this->outVeryVerbose( 'Applying database config to module '.$moduleId.' ...' );		//  tell about this in very verbose mode
			if( !isset( $this->config->modules->{$moduleId} ) )										//  registered module is not installed
				$this->config->modules->{$moduleId}	= (object) [];									//  create an empty module definition in loaded module list
			$module	= $this->config->modules->{$moduleId};											//  shortcut module definition
			if( !isset( $module->config ) )															//  module definition has not configuration
				$module->config	= (object) [];														//  create an empty configuration in module definition
			foreach( $this->config->database as $key => $value )									//  iterate database access information from hymn file
				if( !in_array( $key, ['modules'] ) )												//  skip the found list of resource modules to apply exactly this method to
					$module->config->{$configPrefix.$key}	= $value;								//  set database access information in resource module configuration
		}
		return TRUE;
	}

	protected function applyBaseConfiguredPathsToAppConfig()
	{
		$this->config->paths	= (object) [];
		foreach( self::$pathDefaults as $pathKey => $pathValue )
			if( !isset( $this->config->paths->{$pathKey} ) )
				$this->config->paths->{$pathKey}	= $pathValue;

		if( file_exists( $this->config->paths->config.'config.ini' ) ){
			$data	= parse_ini_file( $this->config->paths->config.'config.ini' );
			foreach( $data as $key => $value ){
				if( preg_match( '/^path\./', $key ) ){
					$key	= preg_replace( '/^path\./', '', $key );
					$key	= ucwords( str_replace( '.', ' ', $key ) );
					$key	= str_replace( ' ', '', lcfirst( $key ) );
					$this->config->paths->{$key}	= $value;
					continue;
				}
				$key	= ucwords( str_replace( '.', ' ', $key ) );
				$key	= str_replace( ' ', '', lcfirst( $key ) );
				$this->config->{$key}	= $value;
			}
		}
	}
}
