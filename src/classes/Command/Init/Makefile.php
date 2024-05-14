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
 *	@package		CeusMedia.Hymn.Command.Init
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Init
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_Init_Makefile extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected string $pathPhar			= "phar://hymn.phar/";
	protected array $argumentOptions	= [
		'backend'		=> [
			'pattern'	=> '/^-b|--backend/',
			'resolve'	=> TRUE,
			'default'	=> FALSE,
		],
		'public'		=> [
			'pattern'	=> '/^-p|--public/',
			'resolve'	=> TRUE,
			'default'	=> FALSE,
		],
		'nonfree'		=> [
			'pattern'	=> '/^-n|--nonfree/',
			'resolve'	=> TRUE,
			'default'	=> FALSE,
		],
	];

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$withBackend = $this->client->arguments->getOption( 'backend' );
		$withPublic = $this->client->arguments->getOption( 'public' );
		$withNonfree = $this->client->arguments->getOption( 'nonfree' );

		$targetFileName	= trim( $this->client->arguments->getArgument( 0 ) );


		/*  --  CREATE MAKE FILE  --  */
		if( !$targetFileName ){
			if( !file_exists( 'Makefile' ) )
				$targetFileName	= 'Makefile';
			else{
				$question	= new Hymn_Tool_CLI_Question( $this->client, "Name of new make file" );
				$question->setType( 'string' )->setDefault( "Makefile.generated" );
				$targetFileName	= $question->setBreak( FALSE )->ask();
			}
		}
		copy( $this->pathPhar."templates/Makefile", $targetFileName );

		$lineFilters	= [];
		if( !$withBackend ){
			$lineFilters[]	= '/PATH_BACKEND/';
			$lineFilters[]	= '/^.backend-/';
		}
		if( !$withPublic )
			$lineFilters[]	= '/MODS_CM_PUBLIC/';
		if( !$withNonfree )
			$lineFilters[]	= '/MODS_CM_NONFREE/';



		if( 0 !== count( $lineFilters ) ){
			$lines	= file( $targetFileName, FILE_IGNORE_NEW_LINES );
			$lines	= array_filter( $lines, static function( $line ) use ( $lineFilters ): bool{
				foreach( $lineFilters as $regex )
					if( preg_match( $regex, $line ) )
						return FALSE;
				return TRUE;
			});
			file_put_contents( $targetFileName, join( PHP_EOL, $lines ).PHP_EOL );
		}

		$this->out( "Make file has been created." );
		$this->out( "" );															//  print empty line as optical separator
	}
}
