<?php

require_once('Picasa/Album.php');
require_once('Picasa/Author.php');
require_once('Picasa/Logger.php');
require_once('Picasa/Cache.php');

/**  
 * Represents a Picasa Account, which holds an array of Albums.  The general idea is to instantiate an instance
 * of this object using XML from Picasa's Atom feed and then access the needed information about the Picasa
 * account through getters and setters.
 *
 * @package Picasa
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @since 1.0 
 */
class Picasa_Account {

	/**
	 * An array of {@link Picsaa_Album} objects for each album in the requested feed.    
	 * @var array 
	 */
	private $albums;         

	/**
	 * The name of the Account's owner. 
	 * @var string
	 * @deprecated Since Version 2.0.  Use {@link $picasaAuthor} instead.
	 */
	private $author;         

	/**
	 * The base URL of the feed that was requested. 
	 * @var string
	 */
	private $id;             

	/**
	 * The account title, probably the owner's username. 
	 * @var string
	 */
	private $title;          

	/**
	 * The account subtitle, probably blank.        
	 * @var string
	 */
	private $subtitle; 

	/**
	 * A URL to the account icon, probably the author's icon.
	 * @var string
	 */
	private $icon; 

	/**
	 * The account author.
	 * @var {@link Picasa_Author}
	 * @since Version 2.0
	 */
	private $picasaAuthor; 

	/** 
	 * The URL to account in PicasaWeb.
	 *
	 * @access private
	 * @var string
	 */
	private $weblink;

	/**
	 * @return array 
	 */
	public function getAlbums () {
		return $this->albums;
	}

	/**
	 * @return string
	 */
	public function getAuthor () {
		return $this->author;
	}

	/**
	 * @return string
	 */
	public function getId () {
		return $this->id;
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
	public function getSubtitle () {
		return $this->subtitle;
	}

	/**
	 * @return string
	 */
	public function getIcon () {
		return $this->icon;
	}

	/**
	 * @return Picasa_Author 
	 */
	public function getPicasaAuthor () {
		return $this->picasaAuthor;
	}

	/**
	 * @return string 
	 * @access public
	 */
	public function getWeblink () {
		return $this->weblink;
	}

	/**
	 * @param array
	 * @return void
	 */
	public function setAlbums ($albums) {
		$this->albums = $albums;
	}

	/**
	 * @param string
	 * @return void
	 */
	public function setAuthor ($author) {
		$this->author = $author;
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
	public function setTitle ($title) {
		$this->title = $title;
	}

	/**
	 * @param string
	 * @return void
	 */
	public function setSubtitle ($subtitle) {
		$this->subtitle = $subtitle;
	}

	/**
	 * @param string
	 * @return void
	 */
	public function setIcon ($icon) {
		$this->icon = $icon;
	}

	/**
	 * @param Picasa_Author
	 * @return void
	 */
	public function setPicasaAuthor ($picasaAuthor) {
		$this->picasaAuthor = $picasaAuthor;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setWeblink ($weblink) {
		$this->weblink = $weblink;
	}


	/**
	 * Constructs an Account object.  
	 * This method assigns Album objects to the Account,
	 * but the Album objects that are constructed will not initially contain Image objects.  This is 
	 * because the XML that is returned from Picasa does not contain individual Image nodes
	 * for each Album, presumeably because it could potentially take a lot of time to fetch
	 * every image in an Account when all you likely need are the Albums.  Starting with version
	 * 3.0 of this API, the images are fetched from Picasa when {@link Picasa_Album::getImages()}
	 * is called and {@link Picasa_Album::$images} is null. 
	 *  
	 * @param string $url    The URL for the specific query that should be returned, as defined 
	 *			 in Picasa's API documentation (http://code.google.com/apis/picasaweb/gdata.html).
	 *			 Optional, the default is null.  If this parameter is null, the xml must
	 *			 come from the $xml parameter.
	 * @param SimpleXMLElement $xml  XML from a Picasa Atom feed represeting a Picasa Account. Optional,
	 *                                      the default is null.  If this parameter and the $url parameters are
	 *                                      both null, a {@link Picasa_Exception} is thrown.  You cannot create
	 *                                      an empty Picasa_Account instance.
	 * @param array $contextArray  An array that can be passed to the PHP built in function {@link stream_context_create()}.
	 *                             It contains useful information when retrieving a feed, including headers that
	 *                             might include needed authorization information.
	 * @param boolean $useCache  You can decide not to cache a specific request by passing false here.  You may
	 *                           want to do this, for instance, if you're requesting a private feed.
	 * @throws Picasa_Exception  If valid XML cannot be retrieved from the URL specified in $url or $xml. 
	 */
	public function __construct ($url=null, SimpleXMLElement $xml=null, $contextArray=null, $useCache=true) {
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
				Picasa_Logger::getLogger()->logIfEnabled('Cached copy not available, requesting account freshly for URL: '.$url);
				$xmldata = @file_get_contents($url, false, $context);
				if ($useCache === true && $xmldata !== false) {
					Picasa_Logger::getLogger()->logIfEnabled('Saving account to cache.');
					Picasa_Cache::getCache()->setInCache($url, $xmldata);
				}
			} else {
				Picasa_Logger::getLogger()->logIfEnabled('Account retreived from cache.');
			}
			if ($xmldata === false) {
				throw Picasa::getExceptionFromInvalidQuery($url, $contextArray);	
			}
			
			try {
				// Load the XML file into a SimpleXMLElement
				$xml = new SimpleXMLElement($xmldata);
			} catch (Exception $e) {
				throw new Picasa_Exception($e->getMessage(), null, $url);
			}
		}

		// The basic data can easily be loaded from the XML
		$this->author = $xml->author->name;
		$this->id = $xml->id;
		$this->title = $xml->title;
		$this->subtitle = $xml->subtitle;
		$this->icon = $xml->icon;
		$this->picasaAuthor = new Picasa_Author($xml->author);

		foreach ($xml->link as $plink) {
			if ($plink['rel'] == 'alternate') {
				$this->weblink = $plink['href'];
			}
		}

		// Create an array to hold the albums in the account
		$this->albums = array();
		$i = 0;

		// Create a blank Album object for each album in the account 
		foreach($xml->entry as $albums) {
			$this->albums[$i] = new Picasa_Album(null,$albums);
			$i++;
		}			
	}

	/**
	 * Constructs a textual representation of everything in the current instantiation of the object.
	 *
	 * @return string
	 */
	public function __toString() {
		$retstring = " 
[ TYPE:        Picasa_Account
  ID:          ".$this->id."
  WEBLINK:     ".$this->weblink."
  TITLE:       ".$this->title."
  SUBTITLE:    ".$this->subtitle."
  ICON:        ".$this->icon."
  AUTHOR:      ".$this->author."
  PICASAAUTHOR:".$this->picasaAuthor."
  ALBUMS:      ";
		foreach ($this->albums as $album) {
		    $retstring .= $album;
		}
		$retstring .= "
]";
		return $retstring;
	}


}
