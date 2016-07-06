<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Install extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";
	protected $force		= FALSE;
	protected $verbose		= FALSE;
	protected $quiet		= FALSE;

	public function run(){
		$this->dry		= $this->client->arguments->getOption( 'dry' );
		$this->force	= $this->client->arguments->getOption( 'force' );
		$this->quiet	= $this->client->arguments->getOption( 'quiet' );
		$this->verbose	= $this->client->arguments->getOption( 'verbose' );

		if( $this->dry )
			Hymn_Client::out( "## DRY RUN: Simulated actions - no changes will take place." );

		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source ){
			$active	= !isset( $source->active ) || $source->active;
			$library->addShelf( $sourceId, $source->path, $active );
		}
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$moduleId	= trim( $this->client->arguments->getArgument() );
		if( $moduleId ){
			$module			= $library->getModule( $moduleId );
			if( $module ){
				$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$relation->addModule( $module, $installType );
			}
		}
		else{
			foreach( $config->modules as $moduleId => $module ){
				if( preg_match( "/^@/", $moduleId ) )
					continue;
				if( !isset( $module->active ) || $module->active ){
					$module			= $library->getModule( $moduleId );
					$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
					$relation->addModule( $module, $installType );
				}
			}
		}

		$installer	= new Hymn_Module_Installer( $this->client, $library, $this->quiet );
		$modules	= $relation->getOrder();
		foreach( $modules as $module ){
			$listInstalled	= $library->listInstalledModules( $config->application->uri );
			$isInstalled	= array_key_exists( $module->id, $listInstalled );
			$isCalledModule	= $moduleId && $moduleId == $module->id;
			$isForced		= $this->force && ( $isCalledModule || !$moduleId );
			if( $isInstalled && !$isForced )
				Hymn_Client::out( "Module '".$module->id."' is already installed" );
			else{
				Hymn_Client::out( "Installing module '".$module->id."' ..." );
				$installType	= $this->client->getModuleInstallType( $module->id, $this->installType );
				$installer->install( $module, $installType, $this->verbose, $this->dry );
			}
		}

/*		//  todo: custom install mode: define SQL to import in hymn file
		if( isset( $config->database->import ) ){
			foreach( $config->database->import as $import ){
				if( file_exists( $import ) )
					$installer->executeSql( file_get_contents( $import ) );							//  broken on this point since extraction to Hymn_Module_SQL
			}
		}*/
	}
}
