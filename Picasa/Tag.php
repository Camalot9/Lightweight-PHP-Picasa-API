<?php

require_once('Picasa/Author.php');
require_once('Picasa/Logger.php');

/**
 * Represents a Tag for Picasa photos.
 *
 * @package Picasa
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @since 3.0
 */
class Picasa_Tag {

	/**
	 * The URL of the Atom feed for the tag.
	 *
	 * @var string 
	 */
	private $id;        

	/**
	 * Time and date the tag was submitted. 
	 *
	 * @var string 
	 */
	private $updated;   

	/**
	 * The title of the tag.
	 *
	 * @var string 
	 */
	private $title;    

	/**
	 * The text of the tag.  
	 *
	 * @var string 
	 */
	private $summary;     

	/**
	 * The author of the comment.     
	 *
	 * @var {@link Picasa_Author}
	 */
	private $author;      

	/**
	 * The number of times the tag occurs in the requested feed.           
	 *
	 * @var int 
	 */
	private $weight; 

	/**
	 * @return string
	 */
	public function getId () {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getUpdated () {
		return $this->updated;
	}

	/**
	 * @return string
	 */
	public function getTitle () {
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getSummary () {
		return $this->summary;
	}

	/**
	 * @return Picasa_Author 
	 */
	public function getAuthor () {
		return $this->author;
	}

	/**
	 * @return int 
	 */
	public function getWeight () {
		return $this->summary;
	}

	/**
	 * @param string
	 * @return void
	 */
	public function setId ($id) {
		$this->id = $id;
	}

	/**
	 * @param string
	 * @return void
	 */
	public function setUpdated ($updated) {
		$this->updated = $updated;
	}

	/**
	 * @param string
	 * @return void
	 */
	public function setTitle ($title) {
		$this->title = $title;
	}

	/**
	 * @param string
	 * @return void
	 */
	public function setSummary ($summary) {
		$this->summary = $summary;
	}

	/**
	 * @param Picasa_Author 
	 * @return void
	 */
	public function setAuthor ($author) {
		$this->author = $author;
	}

	/**
	 * @param int 
	 * @return void
	 */
	public function setWeight ($weight) {
		$this->weight = $weight;
	}


	/**
	 * Constructs a Picasa_Tag object from XML.
	 *
	 * @param SimpleXMLElement $tag   XML representing a Picasa tag.  
	 */
	public function __construct (SimpleXMLElement $tag=null) {
	    if ($tag != null) {
			$namespaces = $tag->getNamespaces(true);

			if (array_key_exists("gphoto", $namespaces)) {
				$gphoto_ns = $tag->children($namespaces["gphoto"]);
				$this->weight = $gphoto_ns->weight;
			} else {
			    	$this->weight = null;
			}

			$this->id = $tag->id;
			$this->title = $tag->title;
			$this->updated = $tag->updated;
			$this->summary = $tag->summary;
			$this->author = new Picasa_Author($tag->author);
	    }
	}

	/**
	 * Constructs a textual representation of everything in the current instantiation of the object.
	 *
	 * @return string
	 */
	public function __toString() {
    		$retString="
      [ TYPE:         Picasa_Tag 
        ID:           ".$this->id."
        UPDATED:      ".$this->updated."
        TITLE:        ".$this->title." 
        SUMMARY:      ".$this->summary."
        WEIGHT:       ".$this->weight."
        AUTHOR:       ".$this->author."
      ]";

	    	return $retString;
	}

	/**
	 * Constructs an array of {@link Picasa_Tag} objects based on the XML taken from either the $xml parameter or from the contents of $url.
	 *
	 * @param string $url             A URL pointing to a Picasa Atom feed that has zero or more "entry" nodes represeing
	 *                                a Picasa tag.  Optional, the default is null.  If this parameter is null, the method will
	 *                                try to get the XML content from the $xml parameter directly.
	 * @param SimpleXMLElement $xml   XML from a Picasa Atom feed that has zero or more "entry" nodes represeing a Picasa tag.  
	 *                                Optional, the default is null.  If the $url parameter is null and the $xml parameter is null,
	 *                                a {@Picasa_Exception} is thrown.  
	 * @param boolean $useCache       You can decide not to cache a specific request by passing false here.  You may
	 *                                want to do this, for instance, if you're requesting a private feed.
	 * @throws Picasa_Exception       If the XML passed (through either parameter) could not be used to construct a {@link SimpleXMLElement}.
	 * @return array                  An array of {@link Picasa_Tag} objects representing all tags in the requested feed.
	 * @link http://php.net/simplexml
	 */
	public static function getTagArray($url=null, SimpleXMLElement $xml=null, $contextArray=null, $useCache=true) {
		if ($url != null) {
			Picasa_Logger::getLogger()->logIfEnabled('Request string: '.$url);
			$context = null;
			$tagXml = false;
			if ($contextArray != null) {
			    	$context = stream_context_create($contextArray);
			}
			if ($useCache === true) {
				$tagXml = Picasa_Cache::getCache()->getIfCached($url);
			}
			if ($tagXml === false) {
				Picasa_Logger::getLogger()->logIfEnabled("Not using cached entry for ".$url);
				$tagXml = @file_get_contents($url, false, $context);
				if ($tagXml != false && $useCache === true) {
					Picasa_Logger::getLogger()->logIfEnabled("Refreshing cache entry for tags.");
					Picasa_Cache::getCache()->setInCache($url, $tagXml);
				}
			}
			if ($tagXml === false) {
				throw Picasa::getExceptionFromInvalidQuery($url, $contextArray);	
			}
		}
		try {
			// Load the XML file into a SimpleXMLElement
			$xml = new SimpleXMLElement($tagXml);
		} catch (Exception $e) {
			throw new Picasa_Exception($e->getMessage(), null, $url);
		}

		$tagArray = array();
		$i = 0;
		foreach($xml->entry as $tag) {
			$tagArray[$i] = new Picasa_Tag($tag);
			$i++;
		}			

		return $tagArray;
	}	

}
