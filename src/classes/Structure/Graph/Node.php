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
 *	@package		CeusMedia.Hymn.Structure.Graph
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Structure.Graph
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Structure_Graph_Node
{
	public Hymn_Structure_Module $module;
//	public Hymn_Structure_Graph $graph;
	public int $level;//	= 0;

	/** @var array<string,Hymn_Structure_Graph_Node> $in */
	public array $in;//		= [];

	/** @var array<string,Hymn_Structure_Graph_Node> $out */
	public array $out;//	= [];

	public function __construct( Hymn_Structure_Module $module, int $level = 0 )
	{
		$this->module	= $module;
		$this->level	= $level;
		$this->in		= [];
		$this->out		= [];
	}

	public function addIn( Hymn_Structure_Graph_Node $node ): self
	{
		$this->in[]		= $node;
		return $this;
	}

	public function addOut( Hymn_Structure_Graph_Node $node ): self
	{
		$this->out[]		= $node;
		return $this;
	}

}