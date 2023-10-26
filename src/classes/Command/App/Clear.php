<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2023 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Clear extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected array $argumentOptions	= array(
		'age'		=> array(
			'pattern'	=> '/^--age=(\S+)$/',
			'resolve'	=> '\\1',
			'default'	=> '-1',
		),
	);

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		if( !file_exists( Hymn_Client::$fileName ) )
			throw new RuntimeException( "Hymn project '".Hymn_Client::$fileName."' is missing. Please run 'hymn init'!" );

		$config		= $this->client->getConfig();
		$actions	= $this->client->arguments->getArguments();
		if( !count( $actions ) )
			$actions	= ['all'];

		foreach( $actions as $action ){
			$wildcard	= in_array( $action, ['*', 'all'] );
			if( in_array( $action, ['cache'] ) || $wildcard ){
				Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );						//  remove modules cache file
			}
			if( in_array( $action, ['locks'] ) || $wildcard ){
				$this->clearLocks();
			}
		}
	}

	protected function clearLocks()
	{
		$age	= (int) $this->client->arguments->getOption( 'age' );
		$age	= $age > 0 ? $age * 60 : 0;
		$index	= new DirectoryIterator( 'config/locks' );
		foreach( $index as $item ){
			if( $item->isDir() || $item->isDot() )
				continue;
			if( !preg_match( '/\.lock$/', $item->getFilename() ) )
				continue;
			$jobId  = preg_replace( '/\.lock$/', '', $item->getFilename() );
			$this->outVerbose( 'age: '.$age );
			$this->outVerbose( 'edge: '.( $age + time() ) );
			$this->outVerbose( 'file: '.filemtime( $item->getPathname() ) );
			if( $age && time() < filemtime( $item->getPathname() ) + $age )
				continue;
			if( $this->flags->dry ){
				$this->outVerbose( 'Would clear lock of job: '.$jobId );
			}
			else {
				$this->outVerbose( 'Removing lock of job: '.$jobId );
				@unlink( $item->getPathname() );
			}
		}
	}
}
