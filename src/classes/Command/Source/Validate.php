<?php /** @noinspection PhpComposerExtensionStubsInspection */
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
class Hymn_Command_Source_Validate extends Hymn_Command_Source_Abstract implements Hymn_Command_Interface
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

		$this->out( "Source: ".$source->title );
		$this->out( "Path:   ".$source->path );
		$modules	= $this->getLibrary()->getAvailableModules( $source->id );
		$this->out( 'Scanning '.count( $modules ).' module(s)...' );

		$nrErrors	= 0;
		$nrModules	= 0;
		foreach( $modules as $moduleId => $module ){
			$errors		= [];
			$this->outVerbose( "- ".$moduleId.'... ', FALSE );
			$result	= $this->validateAvailableModule( $module, $errors );
			$this->outVerbose( $result ? 'OK' : 'INVALID' );
			if( $result )
				continue;
			if( !$this->flags->verbose )
				$this->out( '- '.$moduleId.' invalid:' );
			$this->out( $this->renderErrorList( $errors ) );
			$nrModules++;
			$nrErrors	+= count( $errors );
		}
		if( 0 !== $nrErrors )
			$this->outError( 'Found '.$nrErrors.' error(s) in '.$nrModules.' module(s).', Hymn_Client::EXIT_ON_EXEC );
		$this->out( 'OK' );
	}

	/**
	 * @param array<LibXMLError> $errors
	 * @param int $colLineLength
	 * @return array
	 */
	protected function renderErrorList( array $errors, int $colLineLength = 5 ): array
	{
		$list	= [];
		foreach( $errors as $error ){
			$lineNr	= str_pad( trim( (string) $error->line ), $colLineLength, ' ' );
			$list[]	= '  '.$lineNr.'| '.trim( $error->message );
		}
		return $list;
	}

	protected function validateAvailableModule( Hymn_Structure_Module $module, array & $errors ): bool
	{
		$filePathXml	= $module->absolutePath.'module.xml';
		return $this->validateModuleFileSyntax( $filePathXml, $errors )
			&& $this->validateModuleFileAgainstSchema( $filePathXml, $errors );
	}

	/**
	 *	Validates XML File.
	 *	@param		string				$filePath
	 *	@param		array<LibXMLError>	$errors		Referenced list of errors (instances of LibXMLError)
	 *	@return		bool
	 */
	protected function validateModuleFileAgainstSchema( string $filePath, array & $errors ): bool
	{
		$start	= microtime( TRUE );
		$filePathXsd	= Hymn_Client::$pharPath.'module-1.0.0.xsd';
		/** @noinspection PhpComposerExtensionStubsInspection */
		libxml_use_internal_errors( true );
		$d = new DOMDocument();
		$d->load( $filePath );
		$result	= $d->schemaValidate( $filePathXsd );
		$stop	= round( ( microtime( TRUE ) - $start ) * 1000, 0 );
		$this->outVeryVerbose( 'Schema: '.$stop.'ms ', FALSE );
		if( $result )
			return TRUE;
		/** @noinspection PhpComposerExtensionStubsInspection */
		foreach( libxml_get_errors() as $error )
			$errors[]	= $error;
		libxml_clear_errors();
		return FALSE;
	}

	/**
	 *	Validates XML File.
	 *	@param		string				$filePath
	 *	@param		array<LibXMLError>	$errors		Referenced list of errors (instances of LibXMLError)
	 *	@return		bool
	 */
	protected function validateModuleFileSyntax( string $filePath, array & $errors ): bool
	{
		$validator	= new Hymn_Tool_XML_Validator();
		$start		= microtime( TRUE );
		$result		= $validator->validate( file_get_contents( $filePath ) );
		$stop		= round( ( microtime( TRUE ) - $start ) * 1000, 1 );
		$this->outVeryVerbose( 'Syntax: '.$stop.'ms ', FALSE );
		if( $result )
			return TRUE;

		$error	= new LibXMLError();
		$error->message	= $validator->getError()->error;
		$error->line	= $validator->getError()->line;
		$error->level	= LIBXML_ERR_FATAL;
		$error->file	= $filePath;
		$errors[]	= $error;
		return FALSE;
	}
}


