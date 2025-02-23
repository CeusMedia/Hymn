<?php /** @noinspection PhpUnused */
declare(strict_types=1);

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
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Graph extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $installType	= "link";

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
//		$config		= $this->client->getConfig();

		if( !( file_exists( "config" ) && is_writable( "config" ) ) )
			$this->outError( "Configuration folder is either not existing or not writable", Hymn_Client::EXIT_ON_SETUP );
		$this->outVerbose( "Loading all needed modules into graph…" );

		$library	= $this->getLibrary();
		$relation	= new Hymn_Module_Graph( $this->client, $library );

		foreach( $library->listInstalledModules() as $moduleId => $module ){
			if( str_starts_with( $moduleId, '@' ) )
				continue;
//			$module	= $library->getAvailableModule( $moduleId );
			if( $module->isActive ){
			//	$installType	= $this->client->getModuleInstallType( $moduleId, $this->installType );
				$relation->addModule( $module );
			}
		}

		$targetFileGraph	= $this->client->getConfigPath()."modules.graph";
		$targetFileImage	= $this->client->getConfigPath()."modules.graph.png";
		$graph				= $relation->renderGraphFile( $targetFileGraph );
//		$this->client->out( "Saved graph file to ".$targetFileGraph."." );
		$relation->renderGraphImage( $graph, $targetFileImage );
//		$this->client->out( "Saved graph image to ".$targetFileImage."." );
	}
}
