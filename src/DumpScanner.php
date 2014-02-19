<?php

namespace Mediawiki\Dump;

use Mediawiki\DataModel\EditFlags;
use Mediawiki\DataModel\Page;
use Mediawiki\DataModel\Revision;
use Mediawiki\DataModel\Revisions;
use Mediawiki\DataModel\Title;
use SimpleXMLElement;
use XMLReader;

class DumpScanner {

	/**
	 * @var \XMLReader
	 */
	protected $reader;
	/**
	 * @var DumpQuery
	 */
	protected $query;
	/**
	 * @var string
	 */
	protected $dumpLocation;

	/**
	 * @param string $dumpLocation
	 * @param DumpQuery $query
	 */
	public function __construct( $dumpLocation, DumpQuery $query ) {
		$this->reader = new XMLReader();
		$this->dumpLocation = $dumpLocation;
		$this->query = $query;
	}

	public function scan() {
		$this->reader->open( $this->dumpLocation );

		$result = array();
		while ( $this->reader->read() && $this->reader->name !== 'page' );
		while ( $this->reader->name === 'page' ) {
			$element = new SimpleXMLElement( $this->reader->readOuterXML() );
			$page = $this->getPageFromElement( $element );
			$match = $this->matchPage( $page );

			if( $match ) {
				//TODO allow the user to choose what to return
				$result[] = $page->getId();
			}

			$this->reader->next('page');
		}

		$this->reader->close();

		return $result;
	}

	/**
	 * @param Page $page
	 *
	 * @return bool
	 */
	private function matchPage( Page $page ) {

		//Check namespaces
		if( !count( $this->query->getNamespaceFilters( ) ) === 0 && !in_array( $page->getTitle()->getNs(), $this->query->getNamespaceFilters() ) ) {
			return false;
		}

		//Check Title
		foreach( $this->query->getTitleFilters( DumpQuery::TYPE_CONTAINS ) as $regex ) {
			if( !preg_match( $regex, $page->getTitle()->getTitle() ) ) {
				return false;
			}
		}
		foreach( $this->query->getTitleFilters( DumpQuery::TYPE_MISSING ) as $regex ) {
			if( preg_match( $regex, $page->getTitle()->getTitle() ) ) {
				return false;
			}
		}

		//Check Text
		foreach( $this->query->getTextFilters( DumpQuery::TYPE_CONTAINS ) as $regex ) {
			if( !preg_match( $regex, $page->getRevisions()->getLatest()->getContent() ) ) {
				return false;
			}
		}
		foreach( $this->query->getTextFilters( DumpQuery::TYPE_MISSING ) as $regex ) {
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
						//todo minor / bot
						new EditFlags(
							$node->revision->comment->__toString()
						),
						$node->revision->timestamp->__toString()
					)
				)
			),
			$node->model->__toString()
		);
	}

}