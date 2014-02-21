<?php

namespace Mediawiki\Dump\Test;

use Mediawiki\Dump\DumpQuery;
use PHPUnit_Framework_TestCase;

/**
 * @covers \Mediawiki\Dump\DumpQuery
 */
class DumpQueryTest extends PHPUnit_Framework_TestCase {

	public function testConstruction() {
		$obj = new DumpQuery();
		$this->assertEquals( 0, $obj->getConditionCount() );
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
		$this->assertEquals( count( $filters ), $query->getConditionCount() );
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
		$this->assertEquals( count( $contains ) + count( $missing ), $query->getConditionCount() );
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
		$this->assertEquals( count( $contains ) + count( $missing ), $query->getConditionCount() );
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

	/**
	 * @dataProvider provideDumpQueryObjects
	 */
	public function testSerializeDeserializeRoundtrip( DumpQuery $object ) {
		$jsoned = json_encode( $object->jsonSerialize() );
		$this->assertTrue( is_array( json_decode( $jsoned, true ) ) );

		$dejsoned = DumpQuery::jsonDeserialize( $jsoned );
		$this->assertInstanceOf( '\Mediawiki\Dump\DumpQuery', $dejsoned );
		$this->assertEquals( $object, $dejsoned );
	}

	public function provideDumpQueryObjects() {
		$objs = array();

		$objs[0] = new DumpQuery();

		$objs[1] = new DumpQuery();
		$objs[1]->addNamespaceFilter( 1 );

		$objs[2] = new DumpQuery();
		$objs[2]->addNamespaceFilter( 1 );
		$objs[2]->addNamespaceFilter( 12 );

		$objs[3] = new DumpQuery();
		$objs[3]->addTextFilter( '/asdffaw/i', DumpQuery::TYPE_CONTAINS );
		$objs[3]->addTextFilter( '/missing../i', DumpQuery::TYPE_MISSING );

		$objs[4] = new DumpQuery();
		$objs[4]->addTitleFilter( '/qqqqq/i', DumpQuery::TYPE_CONTAINS );
		$objs[4]->addTitleFilter( '/wwwww.*./i', DumpQuery::TYPE_MISSING );

		$objs[5] = new DumpQuery();
		$objs[5]->addNamespaceFilter( 1 );
		$objs[5]->addNamespaceFilter( 12 );
		$objs[5]->addNamespaceFilter( 100 );
		$objs[5]->addTextFilter( '/asdffaw/i', DumpQuery::TYPE_CONTAINS );
		$objs[5]->addTextFilter( '/missing../i', DumpQuery::TYPE_MISSING );
		$objs[5]->addTextFilter( '/missing22../i', DumpQuery::TYPE_MISSING );
		$objs[5]->addTitleFilter( '/qqqqq/i', DumpQuery::TYPE_CONTAINS );
		$objs[5]->addTitleFilter( '/wwwww.*./i', DumpQuery::TYPE_MISSING );
		$objs[5]->addTitleFilter( '/wwwww22.*./i', DumpQuery::TYPE_MISSING );

		$provided = array();
		foreach( $objs as $obj ) {
			$provided[] = array( $obj );
		}
		return $provided;
	}

} 