<?php

require_once 'Picasa/Image.php';
require_once 'Picasa/Author.php';
require_once 'Picasa/Exception.php';
require_once 'Picasa.php';
require_once 'Picasa/Logger.php';

/**
 * Represents a collection of images that are retrieved outside of an actual Album.  
 * An Image Collection is very similar to an album but it lacks certain attributes like a 
 * formal title and description.  The Picasa_ImageCollection class is appropriate if you
 * are retrieving images by date or tag, regardless of the Album they're in.
 *
 * @package Picasa
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @since Version 1.0
 */

class Picasa_ImageCollection {

	/**
	 * The author of the collection.
	 *
	 * @var string
	 * @access private
	 * @deprecated Deprecated since Version 2.0, use {@link $picasaAuthor} instead.
	 */
	private $author;

	/**
	 * The base URL to the feed that was requested. 
	 *
	 * @var string
	 * @access private
	 */
	private $id; 

	/**
	 * The title given to the collection.  Probably the author's username.
	 *
	 * @var string
	 * @access private
	 */
	private $title;

	/**
	 * The date and time the collection was requested in UTC time.
	 *
	 * @var string
	 * @access private
	 */
	private $updated; 

	/**
	 * An array of Picasa_Image objects.
	 *
	 * @var array
	 * @access private
	 */
	private $images; 

	/**
	 * The URL to the icon for the collection, probably the author's icon.
	 *
	 * @var string
	 * @access private
	 */
	private $icon;  

	/**
	 * The subtitle for the collection.  Probably blank. 
	 *
	 * @var string
	 * @access private
	 */
	private $subtitle;   

	/**
	 * A Picasa_Author object for the author of the collection.  Not all fields will be filled.
	 *
	 * @var {@link Picasa_Author} 
	 * @access private
	 * @since Version 2.0
	 */
	private $picasaAuthor;

	/**
	 *
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $totalResults;

	/**
	 *
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $startIndex;

	/**
	 *
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $itemsPerPage;

	/**
	 * @return string
	 * @access public;
	 */
	public function getAuthor () {
		return $this->author;
	}

	/**
	 * @return string
	 * @access public;
	 */
	public function getId () {
		return $this->id;
	}

	/**
	 * @return string
	 * @access public;
	 */
	public function getTitle () {
		return $this->title;
	}

	/**
	 * @return string
	 * @access public;
	 */
	public function getUpdated () {
		return $this->updated;
	}

	/**
	 * @returnarray 
	 * @access public;
	 */
	public function getImages () {
		return $this->images;
	}

	/**
	 * @return string
	 * @access public;
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * @return string
	 * @access public;
	 */
	public function getSubtitle() {
		return $this->subtitle;
	}

	/**
	 * @return Picasa_Author
	 * @access public;
	 */
	public function getPicasaAuthor () {
		return $this->picasaAuthor;
	}

	/**
	 * @param string
	 * @return void
	 * @access public
	 */
	public function setAuthor ($author) {
		$this->author = $author;
	}

	/**
	 * @param string
	 * @return void
	 * @access public
	 */
	public function setId ($id) {
		$this->id = $id;
	}

	/**
	 * @param string
	 * @return void
	 * @access public
	 */
	public function setTitle ($title) {
		$this->title = $title;
	}

	/**
	 * @param string
	 * @return void
	 * @access public
	 */
	public function setUpdated ($updated) {
		$this->updated = $updated;
	}

	/**
	 * @param array 
	 * @return void
	 * @access public
	 */
	public function setImages ($images) {
		$this->images = $images;
	}

	/**
	 * @param string
	 * @return void
	 * @access public
	 */
	public function setIcon ($icon) {
		$this->icon = $icon;
	}

	/**
	 * @param string
	 * @return void
	 * @access public
	 */
	public function setSubtitle ($subtitle) {
		$this->subtitle = $subtitle;
	}

	/**
	 * @param Picasa_Author
	 * @return void
	 * @access public
	 */
	public function setPicasaAuthor ($picasaAuthor) {
		$this->picasaAuthor = $picasaAuthor;
	}


	/**
	 * Constructs an ImageCollection object from the Picasa XML feed.
         *
         * @param string $url    A query URL constructed according to the Picasa API documentation hosted by
	 *                       Google at {@link http://code.google.com/apis/picasaweb/gdata.html#Add_Album_Manual_Web}.
	 * @param SimpleXMLElement $albums  XML describing a Picasa image collection.  This can be left blank as long as a URL is 
	 *                                  specified in the url parameter that returns valid XML for a Picasa image collection.  If both 
	 *                                  are null, a {@link Picasa_Exception} is thrown.
	 * @param array $contextArray       An array that can be passed to stream_context_create() to generate
	 *                                  a PHP context.  See 
	 *                                  {@link http://us2.php.net/manual/en/function.stream-context-create.php}
	 * @param boolean $useCache  You can decide not to cache a specific request by passing false here.  You may
	 *                           want to do this, for instance, if you're requesting a private feed.
	 * @throws {@link Picasa_Exception} If the XML suppled through either parameter does not contain valid XML.
	 */
	public function __construct ($url=null, SimpleXMLElement $collectionXml=null, $contextArray=null, $useCache=true) {
		if ($url != null) {
			Picasa_Logger::getLogger()->logIfEnabled('Request string: '.$url);
			$context = null;
			$xmldata = false;
			if ($contextArray != null) {
			    	$context = stream_context_create($contextArray);
			}
			if ($useCache === true) {
				$xmldata = Picasa_Cache::getCache()->getIfCached($url);
			}
			if ($xmldata === false) {
				Picasa_Logger::getLogger()->logIfEnabled("Not using cached entry for ".$url);
				$xmldata = @file_get_contents($url, false, $context);
				if ($xmldata != false) {
					Picasa_Cache::getCache()->setInCache($url, $xmldata);
				}
			}
			if ($xmldata === false) {
				throw Picasa::getExceptionFromInvalidQuery($url, $contextArray);	
			}
//print $xmldata;
			try {
				// Load the XML file into a SimpleXMLElement
				$albums = new SimpleXMLElement($xmldata);
			} catch (Exception $e) {
				throw new Picasa_Exception($e->getMessage(), null, $url);
			}
		}

			$namespaces = $albums->getNamespaces(true);
			if (array_key_exists("openSearch",$namespaces)) {
				$os_ns = $albums->children($namespaces["openSearch"]);
				$this->totalResults = $os_ns->totalResults;
			    	$this->startIndex = $os_ns->startIndex;
				$this->itemsPerPage = $os_ns->itemsPerPage;
			} else {
				$this->totalResults = null;
			    	$this->startIndex = null;
				$this->itemsPerPage = null;
			}

			$this->id = $albums->id;
			$this->title = $albums->title;
			$this->updated = $albums->updated;
			$this->icon = $albums->icon;	
			$this->subtitle = $albums->subtitle;

			if ($albums->author != null && $albums->author != "") {
				$this->picasaAuthor = new Picasa_Author($albums->author);
				$this->author = $albums->author->name;
			} else {
			    	$this->picasaAuthor = null;
				$this->author = null;
			}

			$this->images = array();
			$i = 0;
			foreach($albums->entry as $images) {
				$this->images[$i] = new Picasa_Image(null,$images);
				$i++;
			}

	}

	/**
	 * Constructs a textual representation of the current instantiation.
	 *
	 * @return string
	 */
	public function __toString() {
		$retstring = "
  [ TYPE:        Picasa_ImageCollection
    TITLE:       ".$this->title."
    SUBTITLE:    ".$this->subtitle."
    ICON:        ".$this->icon."
    AUTHOR:      ".$this->author."
    ID:          ".$this->id."      
    UPDATED:     ".$this->updated."      
    TOTALRESULTS:".$this->totalResults."
    STARTINDEX:  ".$this->startIndex."
    ITEMSPERPAGE:".$this->itemsPerPage."
    PICASAAUTHOR:".$this->picasaAuthor." 
    IMAGES:      ";
		foreach ($this->images as $image) {
		    $retstring .= $image; 
		}
		$retstring.="
  ]";
		return $retstring;

	}

}
