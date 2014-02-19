<?php

namespace Mediawiki\Dump\ListGenerators;

use Mediawiki\DataModel\EditFlags;
use Mediawiki\DataModel\Page;
use Mediawiki\DataModel\Revision;
use Mediawiki\DataModel\Revisions;
use Mediawiki\DataModel\Title;
use SimpleXMLElement;
use XMLReader;

/**
 * TODO use the new DumpQuery and DumpScanner
 */
class PageFileExtensionListGenerator {

	/**
	 * @var XMLReader
	 */
	protected $reader;

	/**
	 * @var string
	 */
	protected $fileExtension;

	public function __construct( XMLReader $reader, $fileExtension ) {
		$this->reader = $reader;
		$this->fileExtension = $fileExtension;
	}

	/**
	 * @return Page[]
	 */
	public function getPages(){
		$result = array();
		while ( $this->reader->read() && $this->reader->name !== 'page' );
		while ( $this->reader->name === 'page' ) {
			$page = $this->getPageFromNode( new SimpleXMLElement( $this->reader->readOuterXML() ) );
			if( $page->getTitle()->getNs() == 6 && preg_match( '/\.'.$this->fileExtension.'$/i', $page->getTitle()->getTitle() ) ) {
				$result[] = $page;
				echo $page->getTitle()->getTitle() . "\n";
			}

			$this->reader->next('page');
		}
		return $result;
	}

	/**
	 * @param $node
	 *
	 * @return Page
	 */
	private function getPageFromNode( $node ) {
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