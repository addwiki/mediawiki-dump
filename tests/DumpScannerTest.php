<?php

namespace Mediawiki\Dump\Test;

use Mediawiki\Dump\DumpQuery;
use Mediawiki\Dump\DumpScanner;

/**
 * @covers \Mediawiki\Dump\DumpScanner
 * @todo test invalid construction
 */
class DumpScannerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Total number of pages in the XML output mock
	 */
	const totalPages = 2;

	public function getTestDumpScanner( $query ) {
		return new DumpScanner( __DIR__ . '/mockDump.xml', $query );
	}

	public function getQuery() {
		return new DumpQuery();
	}

	public function testConstructionWithOneQuery() {
		$this->getTestDumpScanner( $this->getQuery() );
		$this->assertTrue( true );
	}

	public function testConstructionWithArrayOfQuery() {
		$this->getTestDumpScanner( array( $this->getQuery(), $this->getQuery() ) );
		$this->assertTrue( true );
	}

	public function testNoFilters() {
		$scanner = $this->getTestDumpScanner( $this->getQuery() );

		$result = $scanner->scan();
		$this->assertEquals( self::totalPages, count( $result[0] ) ); // This should also match everything in the XML
	}

	public function testTitleContainsFilter() {
		$scanner = $this->getTestDumpScanner(
			$this->getQuery()
				->addTitleFilter( '/\.pdf$/i', DumpQuery::TYPE_CONTAINS )
		);

		$result = $scanner->scan();
		$this->assertEquals( 1, count( $result[0] ) );
	}

	public function testTitleMissingFilter() {
		$scanner = $this->getTestDumpScanner(
			$this->getQuery()
				->addTitleFilter( '/\.pdf$/i', DumpQuery::TYPE_MISSING )
		);

		$result = $scanner->scan();
		$this->assertEquals( self::totalPages - 1, count( $result[0] ) );
	}

} 