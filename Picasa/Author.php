<?php

require_once('Picasa/Logger.php');
require_once('Picasa/Cache.php');

/**
 * Holds a Picasa user.  
 * Not all fields are guaranteed to be filled.  Name and Uri will likely always contain a valid value.
 * This class should certainly be called "User" instead, but it's way too late to change it now.
 *
 * @author Cameron Hinkle
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @package Picasa
 * @since Version 1.0
 */

class Picasa_Author {

    	/**
	 * The registered name of the user.
	 *
	 * @access private
	 * @var string
	 */
	private $name;

    	/**
	 * The address of their main page on Picasa.
	 *
	 * @access private
	 * @var string
	 */
	private $uri;  

    	/**
	 * The username of the user. 
	 *
	 * @access private
	 * @var string
	 */
	private $user;      

    	/**
	 * The user's Picasa nickname.
	 *
	 * @access private
	 * @var string
	 */
	private $nickname;   

    	/**
	 * The address of an image selected by the user as their thumbnail.
	 *
	 * @access private
	 * @var string
	 */
	private $thumbnail;   

    	/**
	 * @return string
	 * @access public
	 */
	public function getName () {
		return $this->name;
	}

    	/**
	 * @return string
	 * @access public
	 */
	public function getUri () {
		return $this->uri;
	}

    	/**
	 * @return string
	 * @access public
	 */
	public function getUser () {
		return $this->user;
	}

    	/**
	 * @return string
	 * @access public
	 */
	public function getNickname () {
		return $this->nickname;
	}

    	/**
	 * @return string
	 * @access public
	 */
	public function getThumbnail () {
		return $this->thumbnail;
	}

    	/**
	 * @param string
	 * @return void 
	 * @access public
	 */
	public function setName ($name) {
		$this->name = $name;
	}

    	/**
	 * @param string
	 * @return void 
	 * @access public
	 */
	public function setUri ($uri) {
		$this->uri = $uri;
	}

    	/**
	 * @param string
	 * @return void 
	 * @access public
	 */
	public function setUser ($user) {
		$this->user = $user;
	}

    	/**
	 * @param string
	 * @return void 
	 * @access public
	 */
	public function setNickname ($nickname) {
		$this->nickname = $nickname;
	}

    	/**
	 * @param string
	 * @return void 
	 * @access public
	 */
	public function setThumbnail ($thumbnail) {
		$this->thumbnail = $thumbnail;
	}

	/**
	 * Constructs a Picasa_Author object from XML.  It's important to remember that several of the fields in any Picasa_Author
	 * object will be null or blank.  It depends on what type of feed you're requesting.  It's best to do trial and error before
	 * relying on a field to be populated.
	 *
	 * @param  string $author     XML representing the Picasa user, most likely retrieved from an Album or Image object.
	 */
	public function __construct (SimpleXMLElement $author=null) {

		if ($author != null) {
			$namespaces = $author->getNamespaces(true);
			if (array_key_exists("gphoto", $namespaces)) {
				$gphoto_ns = $author->children($namespaces["gphoto"]);
				$this->nickname = $gphoto_ns->nickname;
				$this->thumbnail = $gphoto_ns->thumbnail;
				$this->user = $gphoto_ns->user;
			}

			// If this XML has an interior author entry, go into it
			if ($author->author != null && $author->author->name != null && strcmp($author->author->name, "") != 0) {
				$author = $author->author;		
			}
			
			$this->name = $author->name;
			$this->uri = $author->uri;
		}	
	}

	/**
	 * Constructs a textual representation of the current instantiation.
	 *
	 * @return string
	 */
	public function __toString() {
    		$retString="
        [ TYPE:        Picasa_Author 
          NAME:        ".$this->name."
          USER:        ".$this->user."
          NICKNAME:    ".$this->nickname." 
          THUMBNAIL:   ".$this->thumbnail."
	]";

	    	return $retString;
	}


	/**
	 * Constructs an array of {@link Picasa_Author} objects based on the XML taken from either the $xml parameter or from the contents of $url.
	 *
	 * @param string $url                  A URL pointing to a Picasa Atom feed that has zero or more "entry" nodes represeing
	 *                                     a Picasa author.  Optional, the default is null.  If this parameter is null, the method will
	 *                                     try to get the XML content from the $xml parameter directly.
	 * @param SimpleXMLElement $xml        XML from a Picasa Atom feed that has zero or more "entry" nodes represeing a Picasa author.  
	 *                                     Optional, the default is null.  If the $url parameter is null and the $xml parameter is null,
	 *                                     a {@Picasa_Exception} is thrown.  
	 * @param array $contextArray          An array that can be passed to stream_context_create() to generate
	 *                                     a PHP context.  See 
	 *                                     {@link http://us2.php.net/manual/en/function.stream-context-create.php}
	 * @param boolean $useCache            You can decide not to cache a specific request by passing false here.  You may
	 *                                     want to do this, for instance, if you're requesting a private feed.
	 * @throws {@link Picasa_Exception}    If the XML passed (through either parameter) could not be used to construct a {@link SimpleXMLElement}.
	 * @return array                       An array of {@link Picasa_Author} objects representing all authors in the requested feed.
	 * @see http://php.net/simplexml
	 */
	public static function getAuthorArray($url=null, SimpleXMLElement $xml=null, $contextArray=null, $useCache=true) {
		if ($url != null) {
			$context = null;
			$authorXml = false;
			if ($contextArray != null) {
			    	$context = stream_context_create($contextArray);
			}
			if ($useCache === true) {
				$authorXml = Picasa_Cache::getCache()->getIfCached($url);
			}
			if ($authorXml === false) {
				Picasa_Logger::getLogger()->logIfEnabled("Not using cached entry for ".$url);
				$authorXml = @file_get_contents($url, null, $context);

				if ($useCache === true && $authorXml !== false) {
					Picasa_Logger::getLogger()->logIfEnabled("Refreshing cache entry for key ".$url);
					Picasa_Cache::getCache()->setInCache($url, $authorXml);
				}
			}
			if ($authorXml == false) {
			    throw Picasa::getExceptionFromInvalidQuery($url);	
			}
		}
		try {
			// Load the XML file into a SimpleXMLElement
			$xml = new SimpleXMLElement($authorXml);
		} catch (Exception $e) {
			throw new Picasa_Exception($e->getMessage(), null, $url);
		}

		$authorArray = array();
		$i = 0;
		foreach($xml->entry as $author) {
			$authorArray[$i] = new Picasa_Author($author);
			$i++;
		}			

		return $authorArray;
	}	

}
