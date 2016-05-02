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
class Hymn_Command_Uninstall extends Hymn_Command_Abstract implements Hymn_Command_Interface{

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
		foreach( $config->sources as $sourceId => $source )
			$library->addShelf( $sourceId, $source->path );
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		$moduleId		= trim( $this->client->arguments->getArgument() );
		$listInstalled	= $library->listInstalledModules( $config->application->uri );
		$isInstalled	= array_key_exists( $moduleId, $listInstalled );
		if( !$moduleId )
			Hymn_Client::out( "No module id given" );
		else if( !$isInstalled )
			Hymn_Client::out( "Module '".$moduleId."' is not installed" );
		else{
			$module		= $listInstalled[$moduleId];
			$neededBy	= array();
			foreach( $listInstalled as $installedModuleId => $installedModule )
				if( in_array( $moduleId, $installedModule->relations->needs ) )
					$neededBy[]	= $installedModuleId;
			if( $neededBy && !$this->force ) {
				$list	= implode( ', ', $neededBy );
				$msg	= "Module '%s' is needed by %d other modules (%s)";
				Hymn_Client::out( sprintf( $msg, $module->id, count( $neededBy ), $list ) );
			}
			else{
				$module->path	= 'not_relevant/';
				$installer	= new Hymn_Module_Installer( $this->client, $library, $this->quiet );
				$installer->uninstall( $module, $this->verbose, $this->dry );
			}
		}
	}
}
