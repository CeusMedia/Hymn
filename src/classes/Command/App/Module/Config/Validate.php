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
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Module_Config_Validate extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$moduleId		= $this->client->arguments->getArgument() ?? '';

		if( '' !== $moduleId ){
			$errors	= [];
			$this->out( 'Checking config of installed module '.$moduleId.': ', FALSE );
			$result	= $this->validateInstalledModuleConfig( $moduleId, $errors );
			$this->out( $result ? 'Valid' : 'Invalid' );
			if( !$result )
				$this->out( $this->renderErrorList( $errors ) );
			return;
		}
		$this->out( 'Checking config of all installed modules:' );
		$lines		= [];
		$nrErrors	= 0;
		$nrModules	= 0;
		$modulesInstalled	= $this->getLibrary()->listInstalledModules();
		foreach( $modulesInstalled as $module ){
			$errors	= [];
			$result	= $this->validateInstalledModuleConfig( $module->id, $errors );
			if( $result )
				continue;
			$nrModules++;
			$nrErrors	+= count( $errors );
			$lines[]	= '- '.$module->id;
			foreach( $this->renderErrorList( $errors ) as $errorLine )
				$lines[]	= $errorLine;
		}
		$this->out( 'Scanned '.count( $modulesInstalled ).' modules(s).' );
		$this->out( 'Found '.$nrErrors.' errors(s) in '.$nrModules.' modules(s).' );
		$this->outVerbose( $lines );
	}

	/**
	 * @param array<LibXMLError> $errors
	 * @return array
	 */
	protected function renderErrorList( array $errors ): array
	{
		$list	= [];
		foreach( $errors as $error ){
			$lineNr	= str_pad( trim( $error->line ), 5, ' ' );
			$list[]	= '  '.$lineNr.'| '.trim( $error->message );
		}
		return $list;
	}

	protected function validateInstalledModuleConfig( string $moduleId, array & $errors ): bool
	{
		$pathConfig		= $this->client->getConfigPath();
		$filePathXml	= $pathConfig.'modules/'.$moduleId.'.xml';
		$filePathXsd	= Hymn_Client::$pharPath.'module-1.0.0.xsd';
		/** @noinspection PhpComposerExtensionStubsInspection */
		libxml_use_internal_errors( true );
		$d = new DOMDocument();
		$d->load( $filePathXml );
		$result	= $d->schemaValidate( $filePathXsd );
		if( $result )
			return TRUE;
		/** @noinspection PhpComposerExtensionStubsInspection */
		foreach( libxml_get_errors() as $error )
			$errors[]	= $error;
		libxml_clear_errors();
		return FALSE;
	}
}
