<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Installer
{
	protected Hymn_Client $client;
	protected Hymn_Module_Library $library;
	protected Hymn_Structure_Config_Application $app;
	protected ?object $config;
	protected bool $isLiveCopy				= FALSE;

	/** @var object{dry: bool, quiet: bool, verbose: bool, veryVerbose: bool, force: bool} $flags */
	protected object $flags;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library )
	{
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->app		= $this->config->application;											//  shortcut to application config
		$this->flags	= (object) [
			'dry'			=> (bool) ($this->client->flags & Hymn_Client::FLAG_DRY ),
			'quiet'			=> (bool) ($this->client->flags & Hymn_Client::FLAG_QUIET  ),
			'force'			=> (bool) ($this->client->flags & Hymn_Client::FLAG_FORCE  ),
			'verbose'		=> (bool) ($this->client->flags & Hymn_Client::FLAG_VERBOSE ),
			'veryVerbose'	=> (bool) ($this->client->flags & Hymn_Client::FLAG_VERY_VERBOSE ),
		];

/*		if( isset( $this->app->installMode ) )
			$this->client->out( "Install Mode: ".$this->app->installMode );
		if( isset( $this->app->installType ) )
			$this->client->out( "Install Type: ".$this->app->installType );*/

		if( isset( $this->app->installType ) && 'copy' === $this->app->installType )			//  installation is a copy
			if( isset( $this->app->installMode ) && 'live' === $this->app->installMode )		//  installation has been for live environment
				$this->isLiveCopy	= TRUE;
		if( $this->isLiveCopy )
			$this->client->out( [
				'',
				'ATTENTION: This build is a live installation in copy mode.',
				'There is not uplink to commit file changes to source repository.',
				'',
			] );
	}

	/**
	 *	Configures an installed module by several steps:
	 *	1. set version attributes: install type, source and date
	 *	2. look for mandatory but empty config pairs in original module
	 *	3. get value for these missing pairs from console if also not set in hymn file
	 *	4. combine values from hymn file and console input and apply to module file
	 *	@access		public
	 *	@param		Hymn_Structure_Module		$module			Data object of module to install
	 *	@return		void
	 *	@throws		Exception					if the XML data could not be parsed.
	 */
	public function configure( Hymn_Structure_Module $module ): void
  {
		$source		= $module->install->path.'module.xml';
		$target		= $this->client->getConfigPath().'modules/'.$module->id.'.xml';
		if( !$this->flags->dry ){																//  if not in dry mode
			Hymn_Module_Files::createPath( dirname( $target ) );								//  create folder for module configurations in app
			@copy( $source, $target );															//  copy module configuration into this folder
		}
		else {
			$target	= $source;
		}

		/** @var string $xml */
		$xml	= file_get_contents( $target );
		$xml	= new Hymn_Tool_XML_Element( $xml );
		$type	= $this->app->type ?? 1;
		if( !$this->flags->dry ){																//  if not in dry mode
			$xml->version->setAttribute( 'install-type', $type );
			$xml->version->setAttribute( 'install-source', $module->sourceId );
			$xml->version->setAttribute( 'install-date', date( "c" ) );
		}

		//  get configured module config pairs
		$configModule	= [];
		if( isset( $this->config->modules[$module->id] ) )									//  module not mentioned in hymn file
			if( isset( $this->config->modules[$module->id]->config ) )
				$configModule	= $this->config->modules[$module->id]->config;

		$changeSet	= [];
		foreach( $module->config as $moduleConfigKey => $moduleConfigData ){					//  iterate config pairs of module
			$dataType		= strtolower( trim( $moduleConfigData->type ) );					//  sanitize module config value type
			$isBoolean		= in_array( $dataType, ['boolean', 'bool'] );						//  note whether module config value is boolean
			$isInConfig		= isset( $configModule[$moduleConfigKey] );							//  note whether config value is set in hymn file
			$value			= $moduleConfigData->value;											//  note original module config value
			$configValue	= $isInConfig ? $configModule[$moduleConfigKey] : $value;			//  note configured nodule config value as string
			if( $isBoolean && $isInConfig ){													//  override boolean module config value by hymn file
				$valueAsString	= strtolower( trim( $configValue ) );
				$configValue	= NULL;
				if( in_array( $valueAsString, ['no', 'false', '0'] ) )
					$configValue	= FALSE;
				else if( in_array( $valueAsString, ['yes', 'true', '1'] ) )
					$configValue	= TRUE;
			}
			$hasValue	= $isBoolean ? NULL !== $configValue : '' !== trim( $configValue );
			if( $moduleConfigData->mandatory && !$hasValue ){
				if( $this->flags->quiet ){														//  in quiet mode no input is allowed
					if( $this->flags->force )													//  don't quit (with exception) in force mode
						continue;
					$message	= 'Missing module config value %s:%s';							//  build exception message
					throw new RuntimeException( vsprintf( $message, array(						//  throw exception
						$module->id,
						$moduleConfigKey,
					) ) );
				}
				if( $this->client->flags & Hymn_Client::FLAG_NO_INTERACTION ){
					$message	= 'Config value %s of module %s is missing but mandatory. Questioning is allowed in interactive mode, only.';
					$this->client->outError( vsprintf( $message, [
						$moduleConfigKey,
						$module->id,
					] ), Hymn_Client::EXIT_ON_RUN );
				}
				$configValue	= Hymn_Tool_CLI_Question::getInstance(							//  get new value from console
					$this->client,
					vsprintf( '    Set (unconfigured mandatory) config value %s:%s', [	//  render console input label
						$module->id,
						$moduleConfigKey,
					] ),
					$dataType,																	//  provide data type
					NULL,																//  no default value
					$moduleConfigData->values,													//  get suggested values if set
					FALSE																	//  no break = inline question
				)->ask();
			}
			if( $moduleConfigData->value !== $configValue )
				$changeSet[$moduleConfigKey]	= $configValue;
		}

		foreach( $xml->config as $node ){														//  iterate original module config pairs
			$moduleConfigKey	= (string) $node['name'];										//  shortcut config pair key
			$moduleConfigValue	= $node->getValue();											//  get default config value of module
			$installationValue	= $moduleConfigValue;											//  assume installation value from default value
			if( array_key_exists( $moduleConfigKey, $changeSet ) ){
				$installationValue	= (string) $changeSet[$moduleConfigKey];					//  replace installation value by hymn config value
				$node->setValue( $installationValue );											//  set value from hymn config on XML node
				if( $this->flags->verbose && !$this->flags->quiet ){							//  verbose mode is on
					$message	= '    - configured %s:%s';										//  ...
					$this->client->out( sprintf( $message, $module->id, $moduleConfigKey ) );	//  inform about configured config pair
				}
			}
			$node->setAttribute( 'default', $moduleConfigValue );								//  add attribute to note module default value
			$node->setAttribute( 'original', $installationValue );								//  add attribute to note value during installation
		}
		if( !$this->flags->dry ){																//  not a dry run
			$xml->saveXml( $target );															//  save changed DOM to module file
			Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );						//  remove modules cache file
		}
	}

	public function install( Hymn_Structure_Module $module, string $installType = 'link' ): bool
	{
		$this->client->getFramework()->checkModuleSupport( $module );
		$files	= new Hymn_Module_Files( $this->client );
		$sql	= new Hymn_Module_SQL( $this->client );
		try{
			$files->copyFiles( $module, $installType );											//  copy module files
			$this->configure( $module );														//  configure module
			$sql->runModuleInstallSql( $module/*, $this->isLiveCopy*/ );						//  run SQL scripts, not for live copy builds
			return TRUE;
		}
		catch( Exception $e ){
			$msg	= "Installation of module '%s' failed.\n%s";
			throw new RuntimeException( sprintf( $msg, $module->id, $e->getMessage() ), 0, $e );
		}
	}

	/**
	 * @param Hymn_Structure_Module $module
	 * @return bool
	 */
	public function uninstall( Hymn_Structure_Module $module ): bool
	{
		$files	= new Hymn_Module_Files( $this->client );
		$sql	= new Hymn_Module_SQL( $this->client );
		try{
			$localModule		= $this->library->readInstalledModule( $module->id );
			$pathConfig			= $this->client->getConfigPath();
			$files->removeFiles( $localModule );												//  remove module files
			if( !$this->flags->dry ){															//  not a dry run
				@unlink( $pathConfig.'modules/'.$module->id.'.xml' );							//  remove module configuration file
				Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );					//  remove modules cache file
			}
			$sql->runModuleUninstallSql( $localModule );										//  run SQL scripts
			return TRUE;
		}
		catch( Exception $e ){
			$message	= "Uninstallation of module '%s' failed.\ņ%s";
			$message	= sprintf( $message, $module->id, $e->getMessage() );
			throw new RuntimeException( $message, 0, $e );
		}
	}
}
