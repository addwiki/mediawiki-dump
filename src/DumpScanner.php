<?php

namespace Mediawiki\Dump;

use InvalidArgumentException;
use Mediawiki\DataModel\EditFlags;
use Mediawiki\DataModel\Page;
use Mediawiki\DataModel\Revision;
use Mediawiki\DataModel\Revisions;
use Mediawiki\DataModel\Title;
use RuntimeException;
use SimpleXMLElement;
use XMLReader;

class DumpScanner {

	/**
	 * @var \XMLReader
	 */
	protected $reader;
	/**
	 * @var DumpQuery[]
	 */
	protected $query;
	/**
	 * @var string
	 */
	protected $dumpLocation;

	/**
	 * @param string $dumpLocation
	 * @param DumpQuery|DumpQuery[] $query
	 *
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function __construct( $dumpLocation, $query ) {
		if( !$query instanceof DumpQuery && !is_array( $query ) ) {
			throw new InvalidArgumentException( '$query must be a DumpQuery or array of DumpQuerys' );
		}
		if( !is_string( $dumpLocation ) ) {
			throw new InvalidArgumentException( '$dumpLocation must be a string' );
		}
		if( is_array( $query ) ) {
			foreach( $query as $queryInstance ) {
				if( !$queryInstance instanceof DumpQuery ) {
					throw new InvalidArgumentException( '$query must be a DumpQuery or array of DumpQuerys' );
				}
			}
		}

		if( !is_readable( $dumpLocation ) ) {
			throw new RuntimeException( '$dumpLocation is not readable' );
		}

		if( $query instanceof DumpQuery ) {
			$query = array( $query );
		}

		$this->reader = new XMLReader();
		$this->dumpLocation = $dumpLocation;
		$this->query = $query;
	}

	/**
	 * @throws RuntimeException
	 * @return array of arrays.
	 *         array( 'dumpKey' => array( 'match1', 'match2' ) )
	 */
	public function scan() {
		$openSuccess = $this->reader->open( $this->dumpLocation );
		if( !$openSuccess ) {
			throw new RuntimeException( 'Failed to open XML: '. $this->dumpLocation );
		}

		$result = array();
		while ( $this->reader->read() && $this->reader->name !== 'page' );
		while ( $this->reader->name === 'page' ) {
			$element = new SimpleXMLElement( $this->reader->readOuterXML() );
			$page = $this->getPageFromElement( $element );

			foreach( $this->query as $queryKey => $query ) {
				$match = $this->matchPage( $page, $query );
				if( $match ) {
					//TODO allow the user to choose what to return
					$result[$queryKey][] = $page->getId();
				}
			}

			$this->reader->next('page');
		}

		$this->reader->close();

		return $result;
	}

	/**
	 * @param Page $page
	 * @param DumpQuery $query
	 *
	 * @return bool
	 */
	private function matchPage( Page $page, DumpQuery $query ) {

		//Check namespaces
		if( !count( $query->getNamespaceFilters( ) ) === 0 && !in_array( $page->getTitle()->getNs(), $query->getNamespaceFilters() ) ) {
			return false;
		}

		//Check Title
		foreach( $query->getTitleFilters( DumpQuery::TYPE_CONTAINS ) as $regex ) {
			if( !preg_match( $regex, $page->getTitle()->getTitle() ) ) {
				return false;
			}
		}
		foreach( $query->getTitleFilters( DumpQuery::TYPE_MISSING ) as $regex ) {
			if( preg_match( $regex, $page->getTitle()->getTitle() ) ) {
				return false;
			}
		}

		//Check Text
		foreach( $query->getTextFilters( DumpQuery::TYPE_CONTAINS ) as $regex ) {
			if( !preg_match( $regex, $page->getRevisions()->getLatest()->getContent() ) ) {
				return false;
			}
		}
		foreach( $query->getTextFilters( DumpQuery::TYPE_MISSING ) as $regex ) {
			if( preg_match( $regex, $page->getRevisions()->getLatest()->getContent() ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param SimpleXMLElement $node
	 *
	 * @return Page
	 */
	private function getPageFromElement( SimpleXMLElement $node ) {
		return new Page(
			new Title(
				$node->title->__toString(),
				$node->ns->__toString()
			),
			$node->id->__toString(),
			new Revisions(
				array(
					new Revision(
						$node->revision->id->__toString(),
						$node->revision->text->__toString(),
						$node->revision->username->__toString(),
						new EditFlags(
							$node->revision->comment->__toString(),
							isset( $node->revision->minor ),
							isset( $node->revision->bot )
						),
						$node->revision->timestamp->__toString()
					)
				)
			),
			$node->model->__toString()
		);
	}

}