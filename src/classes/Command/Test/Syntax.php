<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.Test
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Test
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Test_Syntax extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$this->client->arguments->registerOption( 'recursive', '/^-r|--recursive$/', TRUE );
		$this->client->arguments->parse();
		$this->flags->recursive	= $this->client->arguments->getOption( 'recursive' );

		$path	= $this->client->arguments->getArgument( 0 );
		if( !$path )
			$path	= ".";

		Hymn_Tool_Test::checkPhpClasses( $path, $this->flags->recursive, $this->flags->verbose );

/*		Hymn_Tool_Test::checkPhpClasses( "./", FALSE, !FALSE );
		if( file_exists( "classes" ) )
			Hymn_Tool_Test::checkPhpClasses( "./classes", TRUE, !FALSE );
		if( file_exists( "templates" ) )
			Hymn_Tool_Test::checkPhpClasses( "./templates", TRUE, !FALSE );
*/	}
}
