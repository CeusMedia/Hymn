<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.CLI
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Tool_CLI_Config
{
	public static array $pathDefaults	= Hymn_Structure_Config_Paths::DEFAULTS;

	public bool $isLiveCopy			= FALSE;

	protected Hymn_Client $client;

	/** @var ?Hymn_Structure_Config */
	protected ?Hymn_Structure_Config $config		= NULL;

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

	/**
	 *	@return		Hymn_Structure_Config
	 */
	public function readConfig(): Hymn_Structure_Config
	{
		$config	= Hymn_Tool_ConfigFile::read( Hymn_Client::$fileName );
		$app	= $config->application;

		if( NULL !== $app->configPath )
			self::$pathDefaults['config'] = $app->configPath;

		$this->config	= $config;
		$this->applyBaseConfiguredPathsToAppConfig();
		$this->applyAppConfiguredDatabaseConfigToModules();

		$this->isLiveCopy = 'live' === ( $app->installMode ?? '' ) && 'copy' === ( $app->installType ?? '' );
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

		if( [] === $modules ){																		//  no map of resource modules with config option key prefix given on function call
			$modules	= [];																		//  set empty map
			if( '' === ( $this->config->database->modules ?? '' ) )									//  no database resource modules defined in hymn file (default)
				$this->config->database->modules	= 'Resource_Database:access.';					//  set at least pseudo-default resource module from CeusMedia:HydrogenModules
			$parts	= preg_split( '/\s*,\s*/', $this->config->database->modules ) ?: [];		//  split comma separated list if resource modules in registration format
			foreach( $parts as $moduleRegistration ){												//  iterate these module registrations
				$moduleId		= $moduleRegistration;												//  assume module ID to be the while module registration string ...
				$configPrefix	= '';																//  ... and no config prefix as fallback (simplest situation)
				if( str_contains( $moduleRegistration, ':' ) )										//  a prefix definition has been announced
					list( $moduleId, $configPrefix ) = explode( ':', $moduleRegistration, 2 );	//  split module ID and config prefix into variables
				$modules[$moduleId]	= $configPrefix;												//  enlist resource module registration in array form
			}
		}
		foreach( $modules as $moduleId => $configPrefix ){											//  iterate given or found resource module registrations
		//	$this->outVeryVerbose( 'Applying database config to module '.$moduleId.' ...' );		//  tell about this in very verbose mode
			if( !isset( $this->config->modules[$moduleId] ) )										//  registered module is not installed
				$this->config->modules[$moduleId]	= new Hymn_Structure_Config_Module();			//  create an empty module definition in loaded module list
			$module	= $this->config->modules[$moduleId];											//  shortcut module definition
			if( !isset( $module->config ) )															//  module definition has not configuration
				$module->config	= [];																//  create an empty configuration in module definition
			foreach( (array) $this->config->database as $key => $value )							//  iterate database access information from hymn file
				if( !in_array( $key, ['modules'], TRUE ) )									//  skip the found list of resource modules to apply exactly this method to
					$module->config[$configPrefix.$key]	= $value;									//  set database access information in resource module configuration
		}
		return TRUE;
	}

	protected function applyBaseConfiguredPathsToAppConfig(): void
	{
		foreach( self::$pathDefaults as $pathKey => $pathValue )
			if( !isset( $this->config->paths->{$pathKey} ) )
				$this->config->paths->{$pathKey}	= $pathValue;

		if( file_exists( $this->config->paths->config.'config.ini' ) ){
			$data	= parse_ini_file( $this->config->paths->config.'config.ini' ) ?: [];
			foreach( $data as $key => $value ){
				if( preg_match( '/^path\./', $key ) ){
					/** @var string $key */
					$key	= preg_replace( '/^path\./', '', $key );
					$key	= ucwords( str_replace( '.', ' ', $key ) );
					$key	= str_replace( ' ', '', lcfirst( $key ) );
					if( 'scriptsLib' === $key )
						continue;
					$this->config->paths->{$key}	= $value;
					continue;
				}
//				$key	= ucwords( str_replace( '.', ' ', $key ) );
//				$key	= str_replace( ' ', '', lcfirst( $key ) );
//				$this->config->{$key}	= $value;
			}
		}
	}
}
