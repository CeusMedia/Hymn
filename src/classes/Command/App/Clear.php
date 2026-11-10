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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Clear extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected array $argumentOptions	= [
		'age'		=> [
			'pattern'	=> '/^--age=(\S+)$/',
			'resolve'	=> '\\1',
			'default'	=> '-1',
		],
	];

	/**
	 *	Execute this command.
	 *	Clears module cache, removes job locks and log files (*.log, rotated *.log.1, *.log.gz).
	 *	Implements flags: dry, verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		if( !file_exists( Hymn_Client::$fileName ) )
			throw new RuntimeException( "Hymn project '".Hymn_Client::$fileName."' is missing. Please run 'hymn init'!" );

		$actions	= $this->client->arguments->getArguments();
		if( !count( $actions ) )
			$actions	= ['all'];
		$unknown	= array_diff( $actions, ['*', 'all', 'cache', 'locks', 'logs'] );
		if( [] !== $unknown )
			$this->outError( 'Unknown clear type: '.implode( ', ', $unknown ).' (use cache, locks, logs or all)', Hymn_Client::EXIT_ON_INPUT );

		foreach( $actions as $action ){
			$wildcard	= in_array( $action, ['*', 'all'], TRUE );
			if( 'cache' === $action || $wildcard ){
				if( !$this->flags->dry )
					Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );					//  remove modules cache file
				$this->outVerbose( ( $this->flags->dry ? 'Would clear' : 'Cleared' ).' module cache' );
			}
			if( 'locks' === $action || $wildcard )
				$this->clearFiles( 'locks', $this->client->getConfigPath().'locks/', '/\.lock$/' );
			if( 'logs' === $action || $wildcard )
				$this->clearFiles( 'logs', $this->getLogsPath(), '/\.log(\.[0-9]+)?(\.gz)?$/' );
		}
	}

	/**
	 *	Removes files in folder matching pattern, being older than --age minutes, if set.
	 *	@param		string		$type			Type of files for output, eg. locks
	 *	@param		string		$path			Folder of files
	 *	@param		string		$pattern		Regular expression for file names
	 *	@return		int			Number of removed files
	 */
	protected function clearFiles( string $type, string $path, string $pattern ): int
	{
		if( !is_dir( $path ) ){
			$this->outVerbose( 'No '.$type.' folder found: '.$path );
			return 0;
		}
		$age	= (int) $this->client->arguments->getOption( 'age' );
		$edge	= $age > 0 ? time() - $age * 60 : NULL;											//  files modified after this are kept
		$count	= 0;
		foreach( new DirectoryIterator( $path ) as $item ){
			if( !$item->isFile() || 1 !== preg_match( $pattern, $item->getFilename() ) )
				continue;
			if( NULL !== $edge && $item->getMTime() > $edge )
				continue;
			$count++;
			if( $this->flags->dry )
				$this->outVerbose( '- Would remove: '.$item->getFilename() );
			else{
				$this->outVerbose( '- Removing: '.$item->getFilename() );
				@unlink( $item->getPathname() );
			}
		}
		$this->out( vsprintf( '%s %d %s in %s', [
			$this->flags->dry ? 'Would remove' : 'Removed',
			$count,
			$type,
			$path,
		] ) );
		return $count;
	}

	/**
	 *	Returns folder of log files: path "logs" of app, relative to application folder.
	 *	@return		string
	 */
	protected function getLogsPath(): string
	{
		$config	= $this->client->getConfig();
		$path	= rtrim( $config->paths->logs ?? 'logs/', '/' ).'/';
		if( str_starts_with( $path, '/' ) )
			return $path;
		$uri	= trim( $config->application->uri );
		return ( '' !== $uri ? rtrim( $uri, '/' ).'/' : '' ).$path;
	}
}
