<?php

namespace Mediawiki\Dump\Test;

class DumpScannerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Total number of pages in the XML output
	 */
	const totalPages = 2;

	public function getTestDumpScanner( $query ) {
		return new \Mediawiki\Dump\DumpScanner( __DIR__ . '/mockDump.xml', $query );
	}

	public function getQuery() {
		return new \Mediawiki\Dump\DumpQuery();
	}

	public function testNoFilters() {
		$scanner = $this->getTestDumpScanner( $this->getQuery() );

		$result = $scanner->scan();
		$this->assertEquals( self::totalPages, count( $result ) ); // This should also match everything in the XML
	}

	public function testTitleContainsFilter() {
		$scanner = $this->getTestDumpScanner(
			$this->getQuery()
				->addTitleFilter( '/\.pdf$/i', \Mediawiki\Dump\DumpQuery::TYPE_CONTAINS )
		);

		$result = $scanner->scan();
		$this->assertEquals( 1, count( $result ) );
	}

	public function testTitleMissingFilter() {
		$scanner = $this->getTestDumpScanner(
			$this->getQuery()
				->addTitleFilter( '/\.pdf$/i', \Mediawiki\Dump\DumpQuery::TYPE_MISSING )
		);

		$result = $scanner->scan();
		$this->assertEquals( self::totalPages - 1, count( $result ) );
	}

} 