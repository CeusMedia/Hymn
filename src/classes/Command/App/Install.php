<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Install extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected $installType	= "link";

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: database-no, dry, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		if( $this->flags->dry && !$this->flags->quiet )
			$this->client->out( "## DRY RUN: Simulated actions - no changes will take place." );

		$config		= $this->client->getConfig();
//		$this->client->getDatabase()->connect();													//  setup connection to database
		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$moduleIds			= $this->client->arguments->getArguments();
		$defaultShelfId		= $library->getDefaultShelf();
		$activeShelfList	= $library->getActiveShelves();
		$activeShelfIds		= array_keys( $activeShelfList );
		$listInstalled		= $library->listInstalledModules();

		if( $moduleIds ){
			foreach( $moduleIds as $moduleId ){
				$sourceId	= $this->detectModuleSource( $moduleId );
				$sourceId	= $this->client->getModuleInstallShelf( $moduleId, $activeShelfIds, $sourceId );
				$module		= $library->getAvailableModule( $moduleId, $sourceId );
				if( $module->isActive )
					$relation->addModule( $module );
			}
		}
		else{
			$this->client->out( 'Mode: Install ALL ('.count((array)$config->modules).')' );
			foreach( $config->modules as $moduleId => $moduleConfig ){
				if( preg_match( "/^@/", $moduleId ) )
					continue;
				$this->client->outVerbose( 'Module ID: '.$moduleId );
				$sourceId	= $this->detectModuleSource( $moduleId );
				$this->client->outVerbose( 'Source ID: '.$sourceId );
				$sourceId	= $this->client->getModuleInstallShelf( $moduleId, $activeShelfIds, $sourceId );
				$module		= $library->getAvailableModule( $moduleId, $sourceId );
				if( $module->isActive )
					$relation->addModule( $module );
			}
		}

		$installer	= new Hymn_Module_Installer( $this->client, $library );
		$modules	= $relation->getOrder();
		foreach( $modules as $module ){
			try{
				$this->client->getFramework()->checkModuleSupport( $module );
			}
			catch( Exception $e ){
				$this->client->out( 'Error: '.$e->getMessage().'.' );
				continue;
			}
			$installType	= $this->client->getModuleInstallType( $module->id );
//			$installMode	= $this->client->getModuleInstallMode( $module->id );
			$isInstalled	= array_key_exists( $module->id, $listInstalled );
			$isCalledModule	= in_array( $module->id, $moduleIds );
			$isForced		= $this->flags->force && ( $isCalledModule || !$moduleIds );
			if( $isInstalled && !$isForced ){
				if( !$this->flags->quiet ){
					$this->client->outVerbose( "Module '".$module->id."' is already installed" );
					continue;
				}
			}
//			$sourceId	= $this->detectModuleSource( $module->id );
			if( empty( $module->sourceId ) ){
				$this->outError( "Module '".$module->id."' is not assigned to a source - skipped" );
				continue;
			}
			$installType	= $this->client->getModuleInstallType( $module->id, $installType );
			if( !$this->flags->quiet )
				$this->client->out( sprintf(
					"%sInstalling module '%s' (from %s) version %s as %s ...",
					$this->flags->dry ? 'Dry: ' : '',
					$module->id,
					$module->sourceId,
					$module->version,
					$installType
				) );
			$installer->install( $module, $installType, $module->sourceId );
		}

/*		//  todo: custom install mode: define SQL to import in hymn file
		if( isset( $config->database->import ) ){
			foreach( $config->database->import as $import ){
				if( file_exists( $import ) )
					$installer->executeSql( file_get_contents( $import ) );							//  broken on this point since extraction to Hymn_Module_SQL
			}
		}*/
	}

	protected function detectModuleSource( string $moduleId )
	{
		$config		= $this->client->getConfig();
		$library	= $this->getLibrary();
		$defaultId	= $library->getDefaultShelf();
		if( !empty( $config->modules->{$moduleId}->source ) ){
			$sourceByHymn	= trim( $config->modules->{$moduleId}->source );
			if( $library->isAvailableModuleInShelf( $moduleId, $sourceByHymn, FALSE ) )
				return $sourceByHymn;
		}
/*		if( $library->isInstalledModule( $moduleId ) ){
		}*/
		if( $defaultId ){
			if( $library->isAvailableModuleInShelf( $moduleId, $defaultId, FALSE ) )
				return $defaultId;
		}
		$moduleSourceIds	= array_keys( $library->getAvailableModuleShelves( $moduleId ) );
		if( $moduleSourceIds )
			return $moduleSourceIds[0];
		return NULL;
	}
}
