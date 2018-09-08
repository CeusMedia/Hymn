<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2018 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Installer{

	protected $client;
	protected $config;
	protected $library;
	protected $dbc;
	protected $files;
	protected $sql;
	protected $isLiveCopy	= FALSE;
	protected $flags;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->dbc		= $client->setupDatabaseConnection();
		$this->files	= new Hymn_Module_Files( $client );
		$this->sql		= new Hymn_Module_SQL( $client );
		$this->app		= $this->config->application;											//  shortcut to application config
		$this->flags	= (object) array(
			'dry'		=> $this->client->flags & Hymn_Client::FLAG_DRY,
			'quiet'		=> $this->client->flags & Hymn_Client::FLAG_QUIET,
			'verbose'	=> $this->client->flags & Hymn_Client::FLAG_VERBOSE,
		);

/*		if( isset( $this->app->installMode ) )
			$this->client->out( "Install Mode: ".$this->app->installMode );
		if( isset( $this->app->installType ) )
			$this->client->out( "Install Type: ".$this->app->installType );*/

		if( isset( $this->app->installType ) && $this->app->installType === "copy" )			//  installation is a copy
			if( isset( $this->app->installMode ) && $this->app->installMode === "live" )		//  installation has been for live environment
				$this->isLiveCopy	= TRUE;
		if( $this->isLiveCopy ){
			$this->client->out( "" );
			$this->client->out( "ATTENTION: This build is a live installation in copy mode." );
			$this->client->out( "There is not uplink to commit file changes to source repository." );
			$this->client->out( "" );
		}
	}

	/**
	 *	Configures an installed module by several steps:
	 *	1. set version attribtes: install type, source and date
	 *	2. look for mandatory but empty config pairs in original module
	 *	3. get value for these missing pairs from console if also not set in hymn file
	 *	4. combine values from hymn file and console input and apply to module file
	 *	@access		public
	 *	@param		object		$module			Data object of module to install
	 *	@return		void
	 */
	public function configure( $module ){
		$source		= $module->path.'module.xml';
		$target		= $this->client->getConfigPath().'modules/'.$module->id.'.xml';
		if( !$this->flags->dry ){																//  if not in dry mode
			Hymn_Module_Files::createPath( dirname( $target ) );								//  create folder for module configurations in app
			@copy( $source, $target );															//  copy module configuration into this folder
		}
		else {
			$target	= $source;
		}

		$xml	= file_get_contents( $target );
		$xml	= new Hymn_Tool_XmlElement( $xml );
		$type	= isset( $this->app->type ) ? $this->app->type : 1;
		if( !$this->flags->dry ){																//  if not in dry mode
			$xml->version->setAttribute( 'install-type', $type );
			$xml->version->setAttribute( 'install-source', $module->sourceId );
			$xml->version->setAttribute( 'install-date', date( "c" ) );
		}

		//  get configured module config pairs
		$configModule	= (object) array();
		if( isset( $this->config->modules->{$module->id} ) )									//  module not mentioned in hymn file
			if( isset( $this->config->modules->{$module->id}->config ) )
				$configModule	= $this->config->modules->{$module->id}->config;

		//  determine if module is active
		$isActive	= TRUE;																		//  module without main switch are active by default
		if( isset( $module->config['active'] ) ){												//  switch is defined in module config
			$isActive	= $module->config['active']->value;										//  take switch value from module config
			if( isset( $configModule->active ) )												//  switch is also defined in hymn file
				$isActive	= in_array( $configModule->active, array( 'yes', 'true', '1' ) );	//  take switch value from hymn file
		}

		$changeSet	= array();
		foreach( $module->config as $moduleConfigKey => $moduleConfigData ){					//  iterate config pairs of module
			$dataType		= strtolower( trim( $moduleConfigData->type ) );					//  sanitize module config value type
			$isBoolean		= in_array( $dataType, array( 'boolean', 'bool' ) );				//  note whether module config value is boolean
			$isMandatory	= $moduleConfigData->mandatory === 'yes';							//  note whether module config value is mandatory
			$isInConfig		= isset( $configModule->{$moduleConfigKey} );						//  note whether config value is set in hymn file
			$value			= $moduleConfigData->value;											//  note original module config value
			$configValue	= $isInConfig ? $configModule->{$moduleConfigKey} : $value;			//  note configured nodule config value as string
			if( $isBoolean && $isInConfig ){													//  overriden boolean module config value by hymn file
				$valueAsString	= strtolower( trim( $configValue ) );
				$configValue	= NULL;
				if( in_array( $valueAsString, array( 'no', 'false', '0' ) ) )
					$configValue	= FALSE;
				else if( in_array( $valueAsString, array( 'yes', 'true', '1' ) ) )
					$configValue	= TRUE;
			}
			$hasValue	= strlen( $configValue ) > 0;
			if( $isBoolean )
				$hasValue	= $configValue !== NULL;
			if( $isMandatory && !$hasValue ){
				if( $this->flags->quiet ){														//  in quiet mode no input is allowed
					$message	= 'Missing module config value %s:%s';							//  build exception message
					throw new RuntimeException( vsprintf( $message, array(						//  throw exception
						$module->id,
						$moduleConfigKey,
					) ) );
				}
				$configValue	= $this->client->getInput(										//  get new value from console
					vsprintf( '    Set (unconfigured mandatory) config value %s:%s', array(		//  render console input label
						$module->id,
						$moduleConfigKey,
					) ),
					$dataType,																	//  provide data type
					NULL,																		//  no default value
					$moduleConfigData->values,													//  get suggested values if set
					FALSE																		//  no break = inline question
				);
			}
			if( $moduleConfigData->value !== $configValue )
				$changeSet[$moduleConfigKey]	= $configValue;
		}

		foreach( $xml->config as $nr => $node ){												//  iterate original module config pairs
			$moduleConfigKey	= (string) $node['name'];										//  shortcut config pair key
			$moduleConfigValue	= $node->getValue();
			if( array_key_exists( $moduleConfigKey, $changeSet ) ){
				$node->setValue( (string) $changeSet[$moduleConfigKey] );
				if( $this->flags->verbose && !$this->flags->quiet ){							//  verbose mode is on
					$message	= '    - configured %s:%s';										//  ...
					$this->client->out( sprintf( $message, $module->id, $key ) );				//  inform about configured config pair
				}
			}
		}
		if( !$this->flags->dry ){																//  no a dry run
			$xml->saveXml( $target );															//  save changed DOM to module file
			Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );						//  remove modules cache file
		}
	}

	public function install( $module, $installType = "link" ){
		try{
			if( !( $this->client->flags & Hymn_Client::FLAG_NO_FILES ) ){
				$this->files->copyFiles( $module, $installType );								//  copy module files
			}
			$this->configure( $module );														//  configure module
			if( !( $this->client->flags & Hymn_Client::FLAG_NO_DB ) )
				$this->sql->runModuleInstallSql( $module/*, $this->isLiveCopy*/ );				//  run SQL scripts, not for live copy builds
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= "Installation of module '%s' failed.\n%s";
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}

	public function uninstall( $module ){
		try{
			$appUri				= $this->app->uri;
			$localModule		= $this->library->readInstalledModule( $module->id );
			$pathConfig			= $this->client->getConfigPath();

			$localModule->path	= $appUri;
			$this->files->removeFiles( $localModule );											//  remove module files
			if( !$this->flags->dry ){															//  not a dry run
				@unlink( $pathConfig.'modules/'.$module->id.'.xml' );							//  remove module configuration file
				Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );					//  remove modules cache file
			}
			if( !( $this->client->flags & Hymn_Client::FLAG_NO_DB ) )							//  database actions are enabled
				$this->sql->runModuleUninstallSql( $localModule );								//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			$message	= "Uninstallation of module '%s' failed.\ņ%s";
			$message	= sprintf( $message, $localModule->id, $e->getMessage() );
			throw new RuntimeException( $message, 0, $e );
		}
	}
}
