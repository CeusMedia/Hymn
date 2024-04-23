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
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Source_Add extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	protected $questions	= array(
		array(
			'key'		=> 'key',
			'label'		=> "- Source ID",
			'type'		=> 'string',
			'default'	=> 'Local_Modules',
		),
		array(
			'key'		=> 'type',
			'label'		=> "- Source type",
			'type'		=> 'string',
			'default'	=> 'folder',
			'options'	=> ["folder"],
		),
		array(
			'key'		=> 'path',
			'label'		=> "- Source path",
			'type'		=> 'string',
			'default'	=> NULL,
		),
		array(
			'key'		=> 'title',
			'label'		=> "- Source description",
			'type'		=> 'string',
			'default'	=> NULL,
		),
	);

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, force, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$config	= $this->client->getConfig();

		if( !isset( $config->sources ) )
			$config->sources	= [];

		$source			= [];
		$connectable	= FALSE;
		do{
			foreach( $this->questions as $question ){												//  iterate questions
				if( !isset( $source[$question['key']] ) )
					$source[$question['key']]	= $question['default'];
				$input	= new Hymn_Tool_CLI_Question(												//  ask for value
					$this->client,
					$question['label'],
					$question['type'],
					$source[$question['key']],														//  preset default or custom value
					$question['options'] ?? [],												//  realize options
					FALSE																			//  no break = inline question
				);
				$source[$question['key']]	= $input->ask();										//  assign given value
			}
			if( isset( $config->sources[$source['key']] ) )
				$this->client->outError( 'Source with ID "'.$source['key'].'" is already registered.' );
			else if( $source['type'] === "folder" && !file_exists( $source['path'] ) )
				$this->client->outError( 'Path to module library source is not existing.' );
			else
				$connectable	= TRUE;																//  note connectability for loop break
			if( $source['type'] === "folder" ){
				$source['path']	= realpath( $source['path'] );
				$source['path']	= rtrim( $source['path'], '/' ).'/';
			}
		}
		while( !$connectable );																		//  repeat until connectable
		$sourceId		= $source['key'];
		$source['title']	= $source['title'] ? $source['title'] : $source['key'];

		if( $this->flags->dry ){
			if( !$this->flags->quiet )
				$this->out( 'Source "'.$sourceId.'" would have been added.' );
			return;
		}
		$json	= Hymn_Tool_ConfigFile::read( Hymn_Client::$fileName );
		$json->sources[$sourceId] = Hymn_Structure_Config_Source::fromArray( [
			'active'	=> TRUE,
			'title'		=> $source['title'],
			'type'		=> $source['type'],
			'path'		=> $source['path'],
		] );
		Hymn_Tool_ConfigFile::save( $json, Hymn_Client::$fileName );
		if( !$this->flags->quiet )
			$this->out( 'Source "'.$sourceId.'" has been added.' );
	}
}
