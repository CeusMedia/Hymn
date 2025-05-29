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
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Source_Index extends Hymn_Command_Source_Abstract implements Hymn_Command_Interface
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

		$jsonFile	= $source->path.'index.json';
		if( file_exists( $jsonFile ) ){
			$this->out( 'Found index JSON file.' );
			if( $this->flags->verbose )
				$this->printSettings( json_decode( file_get_contents( $jsonFile ) ) );
		}

		$serialFile	= $source->path.'index.serial';
		if( file_exists( $serialFile ) ){
			$this->out( 'Found index serial file.' );
			if( $this->flags->verbose )
				$this->printSettings( unserialize( file_get_contents( $serialFile )) );
		}

//		if( !$this->flags->quiet )
//			$this->client->out( 'Source "'.$source->id.'" has been enabled.' );
	}

	protected function printSettings( object $settings ): void
	{
		unset( $settings->modules );
		$data	= (array) $settings;
		if( count( $data ) ){
			foreach( $data as $key => $value )
				if( $value !== NULL )
				$this->out( ' - '.str_pad( $key.':', 14, ' ', STR_PAD_RIGHT ).$value );
			$this->out( '' );
		}
	}
}
