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
 *	@todo 			handle relations (new relations after update)
 */
class Hymn_Command_Update extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";
	protected $dry			= FALSE;
	protected $force		= FALSE;
	protected $quiet		= FALSE;
	protected $verbose		= FALSE;

	public function run(){
		$this->dry		= $this->client->arguments->getOption( 'dry' );
		$this->force	= $this->client->arguments->getOption( 'force' );
		$this->quiet	= $this->client->arguments->getOption( 'quiet' );
		$this->verbose	= $this->client->arguments->getOption( 'verbose' );

		if( $this->dry )
			Hymn_Client::out( "## DRY RUN: Simulated actions - no changes will take place." );

//		$start		= microtime( TRUE );

		$config		= $this->client->getConfig();
		$library	= new Hymn_Module_Library();
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$modules		= array();																	//  prepare list of modules to update
		$moduleId		= trim( $this->client->arguments->getArgument() );							//  is there a specific module ID is given
		$listInstalled	= $library->listInstalledModules( $config->application->uri );				//  get list of installed modules
		if( !$listInstalled )																		//  application has no installed modules
			return Hymn_Client::out( "No installed modules found" );

		$outdatedModules	= array();																//
		foreach( $listInstalled as $installedModule ){
			$source				= $installedModule->installSource;
			$availableModule	= $library->getModule( $installedModule->id, $source, FALSE );
			if( $availableModule ){
				if( version_compare( $availableModule->version, $installedModule->version, '>' ) ){
					$outdatedModules[$installedModule->id]	= (object) array(
						'id'		=> $installedModule->id,
						'installed'	=> $installedModule->version,
						'available'	=> $availableModule->version,
						'source'	=> $installedModule->installSource,
					);
				}
			}
		}

		if( $moduleId ){
			if( !array_key_exists( $moduleId, $listInstalled ) )
				return Hymn_Client::out( "Module '".$moduleId."' is not installed and cannot be updated" );
			if( !array_key_exists( $moduleId, $outdatedModules ) )
				return Hymn_Client::out( "Module '".$moduleId."' is not outdated and cannot be updated" );
			$outdatedModules	= array( $moduleId => $outdatedModules[$moduleId] );
		}

		foreach( $outdatedModules as $update ){
			$module			= $library->getModule( $update->id );
			$installType	= $this->client->getModuleInstallType( $module->id, $this->installType );
/*			$relation->addModule( $module, $installType );
*/
			$message	= "Updating module '%s' (%s -> %s) ...";
			$message	= sprintf( $message, $module->id, $update->installed, $update->available );
			Hymn_Client::out( $message );
			$installer	= new Hymn_Module_Installer( $this->client, $library, $this->quiet );
			$installer->update( $module, $installType, $this->verbose, $this->dry );
		}
	}
}
