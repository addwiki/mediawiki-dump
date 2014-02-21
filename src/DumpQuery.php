<?php

namespace Mediawiki\Dump;

use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;

class DumpQuery implements JsonSerializable {

	//TODO define output of the query! Titles? Pageids? Page Object?
	//TODO add querying for minor, timestamp, comment, contributor

	const TYPE_CONTAINS = 'contains';
	const TYPE_MISSING = 'missing';

	/**
	 * @var array of namespace filters
	 */
	protected $ns = array();

	/**
	 * @var array of title filters
	 */
	protected $title = array(
		self::TYPE_CONTAINS => array(),
		self::TYPE_MISSING => array()
	);

	/**
	 * @var array of text filters
	 */
	protected $text = array(
		self::TYPE_CONTAINS => array(),
		self::TYPE_MISSING => array()
	);

	/**
	 * basic constructor..
	 * Any filters should be set using the addFilter methods
	 */
	public function __construct() {
		return $this;
	}

	/**
	 * @return array of ints namespace filters
	 */
	public function getNamespaceFilters() {
		return $this->ns;
	}

	/**
	 * @param $type
	 *
	 * @return array of strings
	 */
	public function getTitleFilters( $type ) {
		$this->throwExceptionOnBadType( $type );
		return $this->title[$type];
	}

	/**
	 * @param $type
	 *
	 * @return array of strings
	 */
	public function getTextFilters( $type ) {
		$this->throwExceptionOnBadType( $type );
		return $this->text[$type];
	}

	/**
	 * @param int $filter namespace
	 *
	 * @return $this
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function addNamespaceFilter( $filter ) {
		if( !is_int( $filter ) ) {
			throw new InvalidArgumentException( '$filter must be an integer representing namespace id' );
		}
		if( in_array( $filter, $this->ns ) ) {
			throw new RuntimeException( "Namespace filter of {$filter} already applied" );
		}
		$this->ns[] = $filter;
		return $this;
	}

	public function addTitleFilter( $filter, $type ) {
		$this->throwExceptionOnBadType( $type );
		$this->throwExceptionOnBadRegex( $filter );
		if( in_array( $filter, $this->title[$type] ) ) {
			throw new RuntimeException( "Title filter of {$filter} > {$type} already applied" );
		}
		$this->title[$type][] = $filter;
		return $this;
	}

	public function addTextFilter( $filter, $type ) {
		$this->throwExceptionOnBadType( $type );
		$this->throwExceptionOnBadRegex( $filter );
		if( in_array( $filter, $this->text[$type] ) ) {
			throw new RuntimeException( "Text filter of {$filter} > {$type} already applied" );
		}
		$this->text[$type][] = $filter;
		return $this;
	}

	private function throwExceptionOnBadType( $type ) {
		if( $type !== self::TYPE_CONTAINS && $type !== self::TYPE_MISSING ) {
			throw new InvalidArgumentException( '$type should be one of the DumpQuery::TYPE_ constants' );
		}
	}

	private function throwExceptionOnBadRegex( $string ) {
		$default = ini_get( 'track_errors' );
		ini_set( 'track_errors', 'on' );
		$php_errormsg = '';
		@preg_match( $string, '' );
		ini_set( 'track_errors', $default );
		if( $php_errormsg ) {
			throw new InvalidArgumentException( '$string should be a valid preg regular expression' );
		}
	}

	/**
	 * Return the number of conditions within the query
	 * @returns int
	 */
	public function getConditionCount() {
		return
			count( $this->ns ) +
			count( $this->text[self::TYPE_CONTAINS] ) +
			count( $this->text[self::TYPE_MISSING] ) +
			count( $this->title[self::TYPE_CONTAINS] ) +
			count( $this->title[self::TYPE_MISSING] );
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return sha1( json_encode( $this ) );
	}

	/**
	 * (PHP 5 &gt;= 5.4.0)<br/>
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 */
	public function jsonSerialize() {
		return array(
			'ns' => $this->ns,
			'title' => $this->title,
			'text' => $this->text,
		);
	}

	/**
	 * @param string|array $json
	 *
	 * @throws InvalidArgumentException
	 * @return DumpQuery
	 */
	public static function jsonDeserialize( $json ) {
		if( !is_array( $json ) && !is_string( $json ) ) {
			throw new InvalidArgumentException( 'jsonDeserialize needs an array or string' );
		}

		if( is_string( $json ) ) {
			$array = json_decode( $json, true );
		} else {
			$array = $json;
		}

		$obj = new self;
		foreach( $array['ns'] as $ns ) {
			$obj->addNamespaceFilter( $ns );
		}
		foreach( $array['title'] as $type => $titleFilters ) {
			foreach( $titleFilters as $filter ) {
				$obj->addTitleFilter( $filter, $type );
			}
		}
		foreach( $array['text'] as $type => $textFilters ) {
			foreach( $textFilters as $filter ) {
				$obj->addTextFilter( $filter, $type );
			}
		}
		return $obj;
	}
}