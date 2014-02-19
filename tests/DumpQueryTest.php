<?php

namespace Mediawiki\Dump\Test;

use Mediawiki\Dump\DumpQuery;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Mediawiki\Dump\DumpQuery
 */
class DumpQueryTest extends PHPUnit_Framework_TestCase {

	public function testConstruction() {
		new DumpQuery();
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider provideNamespaceFilters
	 */
	public function testAddNamespaceFilters( $filters ) {
		$query = new DumpQuery();
		foreach( $filters as $ns ) {
			$query->addNamespaceFilter( $ns );
		}
		$this->assertEquals( $filters, $query->getNamespaceFilters() );
	}

	public function provideNamespaceFilters() {
		return array(
			array( array( 1 ) ),
			array( array( 1, 12 ) ),
		);
	}

	/**
	 * @dataProvider provideBadNamespaceFilters
	 */
	public function testAddBadNamespaceFilters( $ns ) {
		$this->setExpectedException( 'InvalidArgumentException', '$filter must be an integer representing namespace id' );
		$query = new DumpQuery();
		$query->addNamespaceFilter( $ns );
	}

	public function provideBadNamespaceFilters() {
		return array(
			array( array() ),
			array( null ),
			array( false ),
		);
	}

	/**
	 * @dataProvider provideRegexFilters
	 */
	public function testAddTitleFilters( $contains, $missing ) {
		$query = new DumpQuery();
		foreach( $contains as $regex ) {
			$query->addTitleFilter( $regex, DumpQuery::TYPE_CONTAINS );
		}
		foreach( $missing as $regex ) {
			$query->addTitleFilter( $regex, DumpQuery::TYPE_MISSING );
		}
		$this->assertEquals( $contains, $query->getTitleFilters( DumpQuery::TYPE_CONTAINS ) );
		$this->assertEquals( $missing, $query->getTitleFilters( DumpQuery::TYPE_MISSING ) );
	}

	/**
	 * @dataProvider provideRegexFilters
	 */
	public function testAddTextFilters( $contains, $missing ) {
		$query = new DumpQuery();
		foreach( $contains as $regex ) {
			$query->addTextFilter( $regex, DumpQuery::TYPE_CONTAINS );
		}
		foreach( $missing as $regex ) {
			$query->addTextFilter( $regex, DumpQuery::TYPE_MISSING );
		}
		$this->assertEquals( $contains, $query->getTextFilters( DumpQuery::TYPE_CONTAINS ) );
		$this->assertEquals( $missing, $query->getTextFilters( DumpQuery::TYPE_MISSING ) );
	}

	public function provideRegexFilters() {
		return array(
			array(
				array( '/^FOO/i' ),
				array(),
			),
			array(
				array(),
				array( '/^FOO/i' ),
			),
			array(
				array( '/^FOO/i' ),
				array( '/^FOO/i' ),
			),
			array(
				array( '/^FOO/i', '/.?[abc]\n$/' ),
				array( '/^FOO/i', '/(abc|d?ef?|g).+?33?/' ),
			),
		);
	}

	/**
	 * @dataProvider provideBadRegex
	 */
	public function testAddBadTitleContainsFilters( $badRegex ) {
		$this->setExpectedException( 'InvalidArgumentException', '$string should be a valid preg regular expression' );
		$query = new DumpQuery();
		$query->addTitleFilter( $badRegex, DumpQuery::TYPE_CONTAINS );
	}

	/**
	 * @dataProvider provideBadRegex
	 */
	public function testAddBadTitleMissingFilters( $badRegex ) {
		$this->setExpectedException( 'InvalidArgumentException', '$string should be a valid preg regular expression' );
		$query = new DumpQuery();
		$query->addTitleFilter( $badRegex, DumpQuery::TYPE_MISSING );
	}

	/**
	 * @dataProvider provideBadRegex
	 */
	public function testAddBadTextContainsFilters( $badRegex ) {
		$this->setExpectedException( 'InvalidArgumentException', '$string should be a valid preg regular expression' );
		$query = new DumpQuery();
		$query->addTextFilter( $badRegex, DumpQuery::TYPE_CONTAINS );
	}

	/**
	 * @dataProvider provideBadRegex
	 */
	public function testAddBadTextMissingFilters( $badRegex ) {
		$this->setExpectedException( 'InvalidArgumentException', '$string should be a valid preg regular expression' );
		$query = new DumpQuery();
		$query->addTextFilter( $badRegex, DumpQuery::TYPE_MISSING );
	}

	public function provideBadRegex() {
		return array(
			array( '' ),
			array( 'FOO' ),
			array( 1234 ),
			array( array() ),
		);
	}

} 