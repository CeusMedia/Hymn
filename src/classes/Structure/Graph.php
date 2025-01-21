<?php
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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */

class Hymn_Structure_Graph
{
	/** @var	Hymn_Module_Library				$library */
	public Hymn_Module_Library $library;

	/** @var	Hymn_Structure_Graph_Node[]		$nodes */
	public array $nodes							= [];

	protected array $errors						= [];

	public function __construct( Hymn_Module_Library $library )
	{
		$this->library	= $library;
	}

	/**
	 *	Adds a module to graph as well as all modules linked as 'needed'.
	 *	Sets status to 'changed'.
	 *	@access		public
	 *	@param		Hymn_Structure_Module	$module		Module data object
	 *	@param		integer					$level		Load level of module, default: 0
	 *	@return		static
	 */
	public function addModule( Hymn_Structure_Module $module, int $level = 0 ): static
	{
//		if( version_compare( $this->client->getFramework()->getVersion(), '0.8.8.2', '<' ) )		//  framework is earlier than 0.8.8.2
//			$module	= $this->library->getAvailableModule( $module->id );							//  load module using library

		if( array_key_exists( $module->id, $this->nodes ) ){										//  module has been added already
			if( $this->nodes[$module->id]->level < $level )											//  this time the level is deeper
				$this->nodes[$module->id]->level	= $level;										//  store deeper level
			return $this;																			//  exit without adding relations again
		}
		$this->nodes[$module->id]	= new Hymn_Structure_Graph_Node( $module, $level );				//  add module to node list by module ID
//		$this->status	= self::STATUS_CHANGED;														//  set internal status to "changed"
		foreach( $module->relations->needs as $relatedComponent ){									//  iterate all modules linked as "needed"
			$neededModuleId	= $relatedComponent->id;
			if( Hymn_Structure_Module_Relation::TYPE_MODULE !== $relatedComponent->type )
				continue;
			if( $relatedComponent->source ){
				if( !$this->library->isAvailableModuleInSource( $neededModuleId, $relatedComponent->source ) ){
					$message	= 'Module %s needs module %s from source %s, which is missing.';
					$this->errors[]	= vsprintf( $message, [
						$module->id,
						$neededModuleId,
						$relatedComponent->source,
					] );
				}
			}
			$neededModule	= $this->library->getAvailableModule( $neededModuleId, $relatedComponent->source );		//  get module data object from module library
			$this->addModule( $neededModule, $level + 1 );											//  add this needed module with increased load level
		}
		return $this;
	}

	/**
	 *	Check for loop in module relations.
	 *	@access		protected
	 *	@param		Hymn_Structure_Graph_Node	$node		Node data object containing module and in and out relations
	 *	@param		integer		$level			Counter of recursion level, 0 by default.
	 *	@return		?Hymn_Structure_Graph_Loop	Object if looping node or null if no loop found
	 */
	public function checkForLoop( Hymn_Structure_Graph_Node $node, int $level = 0, array $steps = [] ): ?Hymn_Structure_Graph_Loop
	{
		if( array_key_exists( $node->module->install->path, $steps ) )								//  been in this module in before
			return new Hymn_Structure_Graph_Loop( $node, $steps );									//  return loop data ...
		$steps[$node->module->install->path]	= $node;											//  note this module in module chain
		foreach( $node->in as $parent ){															//  iterate these relations
			$loop	= $this->checkForLoop( $parent, $level + 1, $steps );						//  recurse for related node
			if( $loop )																				//  found loop
				return $loop;																		//  return looping node to prior rounds
		}
		return NULL;																				//  no loop found
	}

	public function countModuleEdgesToRoot( Hymn_Structure_Graph_Node $node, int $level = 0 ): int
	{
		$count	= $level;
		if( [] !== $node->in ){
			$ways	= [];
			foreach( $node->in as $parentNode )
				$ways[]	= $this->countModuleEdgesToRoot( $parentNode, $level + 1 );
			$count	= max( $ways );
		}
		return $count;
	}

	/**
	 *	@return		array<string>
	 */
	public function getErrors( bool $flush = FALSE ): array
	{
		$list	= $this->errors;
		if( $flush )
			$this->errors	= [];
		return $list;
	}

	/**
	 *	@param		string		$nodeId		Node ID
	 *	@return		?Hymn_Structure_Graph_Node
	 */
	public function getNode( string $nodeId ): ?Hymn_Structure_Graph_Node
	{
		return $this->nodes[$nodeId] ?? NULL;
	}

	/**
	 *	@return		Hymn_Structure_Graph_Node[]
	 */
	public function getNodes(): array
	{
		return $this->nodes;
	}

	public function realizeRelations(): void
	{
		/*  count ingoing and outgoing module links  */
		foreach( $this->nodes as $id => $node ){													//  iterate all nodes
			foreach( $node->module->relations->needs as $neededModuleId => $relation ){				//  iterate all needed modules of node
				$neededModuleId	= $relation->id;
				if( Hymn_Structure_Module_Relation::TYPE_MODULE === $relation->type ){
					$this->nodes[$id]->out[$neededModuleId]	= $this->nodes[$neededModuleId];		//  note outgoing link on this node
					$this->nodes[$neededModuleId]->in[$id]	= $node;								//  note ingoing link on the needed node
				}
			}
		}
//		$this->status	= self::STATUS_LINKED;
	}
}