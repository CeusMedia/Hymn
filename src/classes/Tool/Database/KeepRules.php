<?php
declare(strict_types=1);

/**
 *	Selects database dump files to remove by keep rules.
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
 *	@package		CeusMedia.Hymn.Tool.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	Selects database dump files to remove by keep rules.
 *	Dumps are named dump_<Y-m-d>_<H:i:s>.sql (optionally with suffix, like .gz) and sorted by date, newest first.
 *
 *	Rules:
 *	- daily:   the newest N dumps are kept
 *	- weekly:  the newest N dumps of Sundays are kept as well
 *	- monthly: the newest N dumps of the 1st of a month are kept as well
 *	- yearly:  the newest N dumps of January 1st are kept as well
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool.Database
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2026 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_Database_KeepRules
{
	public const FILE_NAME_PATTERN	= '/^dump_([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9:]+)\.sql.*$/';

	public function __construct(
		protected int $daily = 0,
		protected int $weekly = 0,
		protected int $monthly = 0,
		protected int $yearly = 0,
	)
	{
		foreach( ['daily', 'weekly', 'monthly', 'yearly'] as $rule )
			if( $this->$rule < 0 )
				throw new InvalidArgumentException( 'Keep rule "'.$rule.'" must not be negative' );
	}

	/**
	 *	Returns dump file names to remove, newest first. Other files are ignored.
	 *	@param		list<string>	$fileNames
	 *	@return		list<string>
	 */
	public function getFilesToRemove( array $fileNames ): array
	{
		$dumps	= [];
		foreach( $fileNames as $fileName ){
			$matches	= [];
			if( 1 !== preg_match( self::FILE_NAME_PATTERN, $fileName, $matches ) )
				continue;
			$date	= DateTime::createFromFormat( '!Y-m-d', $matches[1] );
			if( FALSE === $date )
				continue;
			$dumps[$fileName]	= $date;
		}
		krsort( $dumps );																			//  newest first, since names contain date and time

		$counts	= ['daily' => 0, 'weekly' => 0, 'monthly' => 0, 'yearly' => 0];
		$remove	= [];
		foreach( $dumps as $fileName => $date ){
			$isWeekly	= '0' === $date->format( 'w' );												//  Sunday
			$isMonthly	= '1' === $date->format( 'j' );												//  1st of month
			$isYearly	= $isMonthly && '1' === $date->format( 'n' );								//  January 1st
			$counts['daily']	+= 1;
			$counts['weekly']	+= $isWeekly ? 1 : 0;
			$counts['monthly']	+= $isMonthly ? 1 : 0;
			$counts['yearly']	+= $isYearly ? 1 : 0;

			$keep	= $counts['daily'] <= $this->daily
				|| ( $isWeekly && $counts['weekly'] <= $this->weekly )
				|| ( $isMonthly && $counts['monthly'] <= $this->monthly )
				|| ( $isYearly && $counts['yearly'] <= $this->yearly );
			if( !$keep )
				$remove[]	= $fileName;
		}
		return $remove;
	}
}
