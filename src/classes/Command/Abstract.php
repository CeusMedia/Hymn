<?php
abstract class Hymn_Command_Abstract{
	public function __construct( Hymn_Client $client ){
		$this->client = $client;
	}
}
