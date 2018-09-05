<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Source
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Source_Add extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config	= $this->client->getConfig();

		if( !isset( $config->sources ) )
			$config->sources	= (object) array();

		$data	= (object) array(
			'key'	=> 'Local_Modules',
			'type'	=> 'folder',
			'path'	=> NULL,
			'title'	=> NULL,
		);

		$questions	= array(
			(object) array(
				'key'		=> 'type',
				'label'		=> "- Source type",
				'options'	=> array( "folder" ),
			),
			(object) array(
				'key'		=> 'path',
				'label'		=> "- Source path",
			),
			(object) array(
				'key'		=> 'key',
				'label'		=> "- Source ID",
			),
			(object) array(
				'key'		=> 'title',
				'label'		=> "- Source description",
			),
		);

		$connectable	= FALSE;
		do{
			foreach( $questions as $question ){														//  iterate questions
				$input		= $this->client->getInput(												//  ask for value
					$question->label,
					'string',
					$data->{$question->key},														//  shortcut default value
					isset( $question->options ) ? $question->options : array(),						//  realize options
					FALSE																			//  no break = inline question
				);
				$data->{$question->key}	= $input;													//  assign given value
			}
			if( isset( $config->sources->{$data->key} ) )
				$this->client->outError( 'Source with ID "'.$data->key.'" is already registered.' );
			else if( $data->type === "folder" && !file_exists( $data->path ) )
				$this->client->outError( 'Path to module library source is not existing.' );
			else
				$connectable	= TRUE;																//  note connectability for loop break
		}
		while( !$connectable );																		//  repeat until connectable
		$data->title	= $data->title ? $data->title : $data->key;
		$config->sources->{$data->key} = (object) array(
			'active'	=> TRUE,
			'title'		=> $data->title,
			'type'		=> $data->type,
			'path'		=> $data->path,
		);

		$json	= json_decode( file_get_contents( Hymn_Client::$fileName ) );
		$json->sources	= $config->sources;
		file_put_contents( Hymn_Client::$fileName, json_encode( $json, JSON_PRETTY_PRINT ) );
	}
}
?>
