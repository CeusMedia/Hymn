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
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */

/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Database_Keep extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $defaultPath;

	/**
	 *	Execute this command.
	 *	Implements flags: database-no
	 *	Missing flags: force?, quiet, verbose
	 *	@todo		implement missing flags
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

		$arg1			= $this->client->arguments->getArgument();
		$keepDaily		= abs( $this->client->arguments->getOption( 'daily' ) );
		$keepWeekly		= abs( $this->client->arguments->getOption( 'weekly' ) );
		$keepMonthly	= abs( $this->client->arguments->getOption( 'monthly' ) );
		$keepYearly		= abs( $this->client->arguments->getOption( 'yearly' ) );

		if( !$keepDaily )
			$this->client->outError( 'No daily rule given. All database dumps are kept.', Hymn_Client::EXIT_ON_RUN );

		/*  --  GET PATHNAME  --  */
		$pathName	= $this->defaultPath;
		if( $arg1 && file_exists( $arg1 ) &&  is_dir( $arg1 ) )
			$pathName	= $arg1;
		if( !$pathName || !file_exists( $pathName ) )
			$this->client->outError( 'No database dump folder found.', Hymn_Client::EXIT_ON_RUN );
		$pathName	= rtrim( $pathName, '/' ).'/';

		$index	= $this->findFilesInPath( $pathName );
		$list	= $this->collectFilesToRemove( $index, $keepDaily, $keepWeekly, $keepMonthly, $keepYearly );

		if( [] === $list ){
			$this->out( 'All database dumps were matching the rules and are kept.' );
			exit( Hymn_Client::EXIT_ON_RUN );
		}

		if( $this->flags->dry ){
			$this->out( count( $list ).' database dumps would have been removed.' );
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

	/**
	 *	Collect files to remove, based on index of files with keep rule dates.
	 *	Returns list of filenames, selected to be removed.
	 *
	 *	@param		array<string,Hymn_Structure_KeepRuleDate>	$index
	 *	@param		int			$keepDaily
	 *	@param		int			$keepWeekly
	 *	@param		int			$keepMonthly
	 *	@param		int			$keepYearly
	 *	@return		array<string>
	 */
	protected function collectFilesToRemove( array $index, int $keepDaily, int $keepWeekly, int $keepMonthly, int $keepYearly ): array
	{
		$nrDaily	= 0;
		$nrWeekly	= 0;
		$nrMonthly	= 0;
		$nrYearly	= 0;
		$list		= [];
		foreach( $index as $fileName => $file ){
			$nrDaily	+= 1;
			$nrWeekly	+= $file->isWeekly ? 1 : 0;
			$nrMonthly	+= $file->isMonthly ? 1 : 0;
			$nrYearly	+= $file->isYearly ? 1 : 0;
			if( $nrDaily <= $keepDaily )
				continue;
			$isValidWeekly	= $keepWeekly && $file->isWeekly && $nrWeekly <= $keepWeekly;
			$isValidMonthly	= $keepMonthly && $file->isMonthly && $nrMonthly <= $keepMonthly;
			$isValidYearly	= $keepYearly && $file->isYearly && $nrYearly <= $keepYearly;
			if( !( $isValidWeekly || $isValidMonthly || $isValidYearly ) )
				$list[]	= $fileName;
		}
		return $list;
	}

	/**
	 *	Returns map of files with keep rule dates.
	 *	@param		string		$pathName
	 *	@return		array<string,Hymn_Structure_KeepRuleDate>
	 */
	protected function findFilesInPath( string $pathName ): array
	{
		/*  --  LIST ALL FILES  --  */
		$index	= [];
		$regex	= '/^(dump_)([0-9-]+)_([0-9:]+)\.(sql)(.*)$/u';
		foreach( new DirectoryIterator( $pathName ) as $entry ){
			if( $entry->isDir() || $entry->isDot() )
				continue;
			$fileName	= $entry->getFilename();
			if( preg_match( $regex, $fileName ) ){
				$timestamp	= preg_replace( $regex, '\\2 \\3', $fileName );
				$date		= new Hymn_Structure_KeepRuleDate( $timestamp );
				$date->isWeekly		= FALSE;
				$date->isMonthly	= FALSE;
				$date->isYearly		= FALSE;
				if( $date->format( 'N' ) === '0' )
					$date->isWeekly	= TRUE;
				if( $date->format( 'j' ) === '1' )
					$date->isMonthly	= TRUE;
				if( $date->format( 'j' ) === '1' && $date->format( 'n' ) === '1' )
					$date->isYearly	= TRUE;
				$index[$fileName]	= $date;
			}
		}
		krsort( $index );
		return $index;
	}
}

