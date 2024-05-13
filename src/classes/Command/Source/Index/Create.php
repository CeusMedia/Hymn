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
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Source_Index_Create extends Hymn_Command_Source_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags: dry, force, quiet, verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$source	= $this->getSourceByArgument();
		if( NULL === $source )
			return;

		$library	= $this->getLibrary();
		$modules	= $library->getAvailableModules( $source->id );
		if( !count( $modules ) )
			$this->outError( 'No available modules found.', Hymn_Client::EXIT_ON_RUN );

		$this->out( vsprintf( 'Found %2$d available modules in source %1$s', [
			$source->id, count( $modules ),
		] ) );

		$this->out( sprintf( 'Path: %1$s', $source->path ) );

		$settings		= new Hymn_Tool_SourceIndex_IniReader( $source->path );
		$moduleFilter	= new Hymn_Tool_SourceIndex_ModuleFilter( $source->path );
		$modules		= $moduleFilter->filter( $modules );

		$this->out( 'Creating index JSON file...' );
		$jsonRenderer	= new Hymn_Tool_SourceIndex_JsonRenderer();
		$jsonRenderer->setSettings( $settings )->setModules( $modules );
		file_put_contents( $source->path.'index.json', $jsonRenderer->render() );

		$this->out( 'Creating index serial file...' );
		$serialRenderer	= new Hymn_Tool_SourceIndex_SerialRenderer();
		$serialRenderer->setSettings( $settings )->setModules( $modules );
		file_put_contents( $source->path.'index.serial', $serialRenderer->render() );
	}
}
