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
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
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
	public function run()
	{
		$config	= $this->client->getConfig();

		if( !isset( $config->sources ) )
			$config->sources	= (object) [];

		$shelf			= [];
		$connectable	= FALSE;
		do{
			foreach( $this->questions as $question ){												//  iterate questions
				if( !isset( $shelf[$question['key']] ) )
					$shelf[$question['key']]	= $question['default'];
				$input	= new Hymn_Tool_CLI_Question(												//  ask for value
					$this->client,
					$question['label'],
					$question['type'],
					$shelf[$question['key']],														//  preset default or custom value
					isset( $question['options'] ) ? $question['options'] : [],					//  realize options
					FALSE																			//  no break = inline question
				);
				$shelf[$question['key']]	= $input->ask();										//  assign given value
			}
			if( isset( $config->sources->{$shelf['key']} ) )
				$this->client->outError( 'Source with ID "'.$shelf['key'].'" is already registered.' );
			else if( $shelf['type'] === "folder" && !file_exists( $shelf['path'] ) )
				$this->client->outError( 'Path to module library source is not existing.' );
			else
				$connectable	= TRUE;																//  note connectability for loop break
			if( $shelf['type'] === "folder" ){
				$shelf['path']	= realpath( $shelf['path'] );
				$shelf['path']	= rtrim( $shelf['path'], '/' ).'/';
			}
		}
		while( !$connectable );																		//  repeat until connectable
		$shelfId		= $shelf['key'];
		$shelf['title']	= $shelf['title'] ? $shelf['title'] : $shelf['key'];

		if( $this->flags->dry ){
			if( !$this->flags->quiet )
				$this->out( 'Source "'.$shelfId.'" would have been added.' );
			return;
		}
		$json	= json_decode( file_get_contents( Hymn_Client::$fileName ) );
		$json->sources->{$shelfId} = (object) [
			'active'	=> TRUE,
			'title'		=> $shelf['title'],
			'type'		=> $shelf['type'],
			'path'		=> $shelf['path'],
		];
		file_put_contents( Hymn_Client::$fileName, json_encode( $json, JSON_PRETTY_PRINT ) );
		if( !$this->flags->quiet )
			$this->out( 'Source "'.$shelfId.'" has been added.' );
	}
}
