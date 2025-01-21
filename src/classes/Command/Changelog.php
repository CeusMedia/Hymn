<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	Displays the changelog of this hymn version.
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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	Displays the changelog of this hymn version.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Command_Changelog extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Displays the changelog of this hymn version.
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$filePath	= Hymn_Client::$pharPath.'.changelog';

		if( !file_exists( $filePath ) )
			$this->client->outError( 'No changelog file found.', Hymn_Client::EXIT_ON_LOAD );
		$this->out( file_get_contents( $filePath ) );
	}
}
