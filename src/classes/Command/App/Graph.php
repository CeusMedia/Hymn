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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Graph extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config		= $this->client->getConfig();

		if( !( file_exists( "config" ) && is_writable( "config" ) ) )
			return $this->client->out( "Configuration folder is either not existing or not writable" );
		$this->client->outVerbose( "Loading all needed modules into graph…" );

		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );
		foreach( $config->modules as $moduleId => $module ){
			if( preg_match( "/^@/", $moduleId ) )
				continue;
			if( $module->isActive ){
				$module			= $library->getModule( $moduleId );
			//	$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$relation->addModule( $module );
			}
		}

		$targetFileGraph	= $this->client->getConfigPath()."modules.graph";
		$targetFileImage	= $this->client->getConfigPath()."modules.graph.png";
		$graph				= $relation->renderGraphFile( $targetFileGraph );
//		if( !$this->flags->quiet )
//			$this->client->out( "Saved graph file to ".$targetFileGraph."." );
		$relation->renderGraphImage( $graph, $targetFileImage );
//		if( !$this->flags->quiet )
//			$this->client->out( "Saved graph image to ".$targetFileImage."." );
	}
}
