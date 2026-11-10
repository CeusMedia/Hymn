<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2014-2026 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Database_Keep extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $defaultPath;

	/**
	 *	Execute this command.
	 *	Removes database dumps not matching keep rules, see Hymn_Tool_Database_KeepRules.
	 *	Implements flags: dry, verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$this->denyOnProductionMode();

		/*  --  REGISTER OPTIONS AND PARSE AGAIN  --  */
		$this->client->arguments->registerOption( 'daily', '/^--daily=(\d+)$/', '\\1', 0 );
		$this->client->arguments->registerOption( 'weekly', '/^--weekly=(\d+)$/', '\\1', 0);
		$this->client->arguments->registerOption( 'monthly', '/^--monthly=(\d+)$/', '\\1', 0 );
		$this->client->arguments->registerOption( 'yearly', '/^--yearly=(\d+)$/', '\\1', 0 );
		$this->client->arguments->parse();

		$arg1		= $this->client->arguments->getArgument();
		$rules		= new Hymn_Tool_Database_KeepRules(
			(int) $this->client->arguments->getOption( 'daily' ),
			(int) $this->client->arguments->getOption( 'weekly' ),
			(int) $this->client->arguments->getOption( 'monthly' ),
			(int) $this->client->arguments->getOption( 'yearly' )
		);
		if( 0 === (int) $this->client->arguments->getOption( 'daily' ) )
			$this->client->outError( 'No daily rule given. All database dumps are kept.', Hymn_Client::EXIT_ON_INPUT );

		/*  --  GET PATHNAME  --  */
		$pathName	= $this->defaultPath;
		if( NULL !== $arg1 && is_dir( $arg1 ) )
			$pathName	= $arg1;
		if( !is_dir( $pathName ) )
			$this->client->outError( 'No database dump folder found.', Hymn_Client::EXIT_ON_RUN );
		$pathName	= rtrim( $pathName, '/' ).'/';

		$fileNames	= array_values( array_diff( scandir( $pathName ) ?: [], ['.', '..'] ) );
		$list		= $rules->getFilesToRemove( $fileNames );

		if( [] === $list ){
			$this->out( 'All database dumps were matching the rules and are kept.' );
			return;
		}

		if( $this->flags->dry ){
			$this->out( count( $list ).' database dumps would have been removed:' );
			foreach( $list as $fileName )
				$this->out( '- '.$fileName );
			return;
		}

		foreach( $list as $fileName ){
			$this->client->outVerbose( '- Removing: '.$fileName );
			@unlink( $pathName.$fileName );
		}
		$this->out( count( $list ).' database dumps removed.' );
	}

	protected function __onInit(): void
	{
		$this->defaultPath	= $this->client->getConfigPath().'sql/';
	}
}
