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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Info extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( !file_exists( Hymn_Client::$fileName ) )
			throw new RuntimeException( "Hymn project '".Hymn_Client::$fileName."' is missing. Please run 'hymn init'!" );

		$config		= $this->client->getConfig();

		$this->client->out( "Application Settings:" );
		foreach( $config->application as $key => $value ){
			if( is_object( $value ) )
				$value	= json_encode( $value, JSON_PRETTY_PRINT );
			$this->client->out( "- ".$key." => ".$value );
		}
		if( $this->flags->verbose ){
			$framework	= $this->client->getFramework();
			if( $framework->isInstalled() )
				$this->client->out( 'Framework: Hydrogen v'.$framework->getVersion() );
			else
				$this->client->out( 'Framework: - not installed -' );
		}
		if( $this->flags->verbose ){
			$this->client->out( '' );
			$this->client->runCommand( 'source-list' );
			$this->client->out( '' );
			$this->client->runCommand( 'modules-installed' );
			$this->client->out( '' );
			$this->client->runCommand( 'modules-updatable' );
//			$this->client->runCommand( 'app-status', array(), array(), array( 'verbose', 'very-verbose' ) );
			$this->client->out( '' );
			$this->client->runCommand( 'modules-required', array(), array(), array( 'verbose', 'very-verbose' ) );
		}
	}
}
