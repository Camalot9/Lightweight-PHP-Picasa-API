<?php

require_once 'Picasa/Image.php';
require_once 'Picasa.php';
require_once 'Picasa/Logger.php';
require_once 'Picasa/Cache.php';

/**
 * Holds a Picasa album.
 * 
 * @package Picasa
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @since Version 1.0
 */

class Picasa_Album {
    	/**
	 * An array that can be passed to stream_create_context() to get a context.  This context array
	 * is used rather than just an auth token because the context array holds both the auth token
	 * and the type of authentication that was performed.  The context can be passed as a parameter
	 * when instantiating a {@link Picasa} object to make the instantiation authenticated.
	 *
	 * @var array
	 * @access private
	 */
	private $contextArray;

	/**
	 * The username of the user who uploaded the album. 
	 *
	 * @var string
	 * @access private
	 * @deprecated Deprecated since Version 2.0, use {@link $picasaAuthor} instead.
	 */
	private $author;

	/**
	 * The URL of the image's page on Picasa Web.
	 *
	 * @var string
	 * @access private
	 */	
	private $id;          

	/**
	 * The image's title, as set by the author.
	 *
	 * @var string
	 * @access private
	 */
	private $title;    

	/**
	 * The date the album was originally published.
	 *
	 * @var string
	 * @access private
	 */
	private $published;   

	/**
	 * The date that the album was last updated. 
	 *
	 * @var string
	 * @access private
	 */
	private $updated;   

	/**
	 * It's unclear what this field is for.  It is likely blank.
	 *
	 * @var string
	 * @access private
	 */
	private $summary;  

	/**
	 * Indicates whether the album is public or private.
	 *
	 * @var string
	 * @access private
	 */
	private $rights;      

	/**
	 * The location that the album is listed as being taken at.
	 *
	 * @var string
	 * @access private
	 */
	private $location;    

	/**
	 * An array of {@link Picasa_Image} objects representing each photo in the album. 
	 *
	 * @var array
	 * @access private
	 */
	private $images;    

	/**
	 * The URL of the album's cover (160px x 160px). 
	 *
	 * @var string
	 * @access private
	 */
	private $icon;    

	/**
	 * A numberic value assigned to the album by Picasa, unique across all Picasa albums.
	 *
	 * @var string
	 * @access private
	 */
	private $idnum; 

	/**
	 * A description of the album, entered by the author.
	 *
	 * @var string
	 * @access private
	 */
	private $subtitle;

	/**
	 * The number of photos in the album.
	 *
	 * @var int 
	 * @access private
	 */
	private $numphotos;

	/**
	 * A Picasa_Author object representing the album's author. 
	 *
	 * @var {@link Picasa_Author}
	 * @access private
	 */
	private $picasaAuthor;

	/**
	 * An array of {@link Picasa_Tag} objects representing all tags found in the album.
	 *
	 * @var array 
	 * @access private
	 * @since Version 2.0
	 */
	private $tags;

	/**
	 * The location that the photos in the album were taken in GML format.
	 *
	 * @link http://en.wikipedia.org/wiki/Geography_Markup_Language
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $gmlPosition;

	/**
	 * The number of photos that can still be uploaded to the album without reaching the album's limit.
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $photosRemaining;

	/**
	 * The size of the current album with all the photos, in bytes.
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $bytesUsed;

	/**
	 * Determines whether or not commenting is allowed on pictures in the album.
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $commentingEnabled;

	/**
	 * The number of comments made for all photos in the album.
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $numComments;

	/**
	 * An array of {@link Picasa_Comment} objects, one for each comment in the album.
	 *
	 * @var array
	 * @access private
	 * @since Version 3.0
	 */
	private $comments;

	/**
	 * The link to use when editing the album.
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $editLink;

	/**
	 * The number of miliseconds after January 1st, 1970 that the image was taken.
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $timestamp;

	/** 
	 * The URL to album PicasaWeb.
	 *
	 * @access private
	 * @var string
	 */
	private $weblink;


	/**
	 * @return string
	 * @access public
	 */
	public function getAuthor () {
		return $this->author;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getId () {
		return $this->id;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getTitle () {
		return $this->title;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getPublished () {
		return $this->published;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getUpdated () {
		return $this->updated;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getSummary () {
		return $this->summary;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getRights () {
		return $this->rights;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getLocation () {
		return $this->location;
	}

	/**
	 * The $images feild can somtimes be null because it's not always included in the XML used to create some objects.  If
	 * that is the case, query the album feed using {@link Picasa::getAlbumById()} to get the images in the album.  This
	 * is done when the client tries to access the images array so that it's not done if it's not requested.
	 *
	 * @return array
	 * @access public
	 */
	public function getImages () {
	    	/* If $this originally came from xml in an Account object, $this->images will be null.  In that case, get the album
		   directly from a url, which will include all the images, and then just take the images field from that album.  A 
		   tad inneficient, but only marginally so.  And it's better than just not having access to the images. */
		if ($this->images === null && $this->numphotos > 0) {
			Picasa_Logger::getLogger()->logIfEnabled("Images was null, requesting from Picasa...");
			if ($this->picasaAuthor !== null) {
		    		$picasa = new Picasa(null, null, null, $this->contextArray);
				$album = $picasa->getAlbumById($this->picasaAuthor->getUser(), $this->idnum);
				$this->images = $album->getImages();
			}
		}
		return $this->images;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getIcon() {
		return $this->icon;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getIdnum() {
		return $this->idnum;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getSubtitle() {
		return $this->subtitle;
	}

	/**
	 * @return int
	 * @access public
	 */
	public function getNumphotos() {
		return $this->numphotos;
	}

	/**
	 * @return Picasa_Author
	 * @access public
	 */
	public function getPicasaAuthor () {
		return $this->picasaAuthor;
	}

	/**
	 * @return array
	 * @access public
	 */
	public function getTags() {
	    	if ($this->tags == null) {
			Picasa_Logger::getLogger()->logIfEnabled("Tags was null, requesting from Picasa...");
			$picasa = new Picasa(null, null, null, $this->contextArray);
			$this->tags = $picasa->getTagsByUsername($this->picasaAuthor->getUser(), $this->idnum, 1000, 1, $this->rights);
		}
		return $this->tags;
	}

	/**
	 * @return string
	 * @access public
	 */
	public function getGmlPosition() {
		return $this->gmlPosition;
	}

	/**
	 * @return int
	 * @access public
	 */
	public function getPhotosRemaining() {
		return $this->photosRemaining;
	}

	/**
	 * @return int
	 * @access public
	 */
	public function getBytesUsed() {
		return $this->bytesUsed;
	}

	/**
	 * @return boolean
	 * @access public
	 */
	public function getCommentingEnabled() {
		return $this->commentingEnabled;
	}

	/**
	 * @return int
	 * @access public
	 */
	public function getNumComments() {
		return $this->numComments;
	}

	/**
	 * @return array 
	 * @access public
	 */
	public function getComments() {
		if ($this->comments == null) {
			Picasa_Logger::getLogger()->logIfEnabled("Comments was null, requesting from Picasa...");
			$picasa = new Picasa(null, null, null, $this->contextArray);
			$this->comments = $picasa->getCommentsByUsername($this->picasaAuthor->user, $this->idnum, 1000, 1, $this->rights);
		}
		return $this->comments;
	}


	/**
	 * @return string 
	 * @access public
	 */
	public function getEditLink() {
		return $this->editLink;
	}

	/**
	 * @return string 
	 * @access public
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * @return string 
	 * @access public
	 */
	public function getWeblink () {
		return $this->weblink;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setAuthor ($author) {
		$this->author = $author;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setId ($id) {
		$this->id = $id;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setTitle ($title) {
		$this->title = $title;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setPublished ($published) {
		$this->published = $published;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setUpdated ($updated) {
		$this->updated = $updated;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setSummary ($summary) {
		$this->summary = $summary;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setRights ($rights) {
		$this->rights = $rights;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setLocation ($location) {
		$this->location = $location;
	}

	/**
	 * @param array 
	 * @return void
	 * @access public;
	 */
	public function setImages ($images) {
		$this->images = $images;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setIcon ($icon) {
		$this->icon = $icon;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setIdnum ($idnum) {
		$this->idnum = $idnum;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setSubtitle ($subtitle) {
		$this->subtitle = $subtitle;
	}

	/**
	 * @param int
	 * @return void
	 * @access public;
	 */
	public function setNumphotos ($numphotos) {
		$this->numphotos = $numphotos;
	}

	/**
	 * @param Picasa_Author
	 * @return void
	 * @access public;
	 */
	public function setPicasaAuthor ($picasaAuthor) {
		$this->picasaAuthor = $picasaAuthor;
	}

	/**
	 * @param string
	 * @return void
	 * @access public;
	 */
	public function setGmlPosition($gmlPosition) {
		$this->gmlPosition = $gmlPosition;
	}

	/**
	 * @param int
	 * @return void
	 * @access public;
	 */
	public function setPhotosRemaining($photosRemaining) {
		$this->photosRemaining = $photosRemaining;
	}

	/**
	 * @param int
	 * @return void
	 * @access public;
	 */
	public function setBytesUsed($bytesUsed) {
		 $this->bytesUsed = $bytesUsed;
	}

	/**
	 * @param boolean 
	 * @return void
	 * @access public;
	 */
	public function setCommentingEnabled($commentingEnabled) {
		 $this->commentingEnabled= $commentingEnabled;
	}

	/**
	 * @param int
	 * @return void
	 * @access public;
	 */
	public function setNumComments($numComments) {
		 $this->numComments = $numComments;
	}

	/**
	 * @param array
	 * @return void
	 * @access public;
	 */
	public function setComments($comments) {
		 $this->comments = $comments;
	}


	/**
	 * @param string  
	 * @return void
	 * @access public;
	 */
	public function setEditLink($editLink) {
		 $this->editLink = $editLink;
	}

	/**
	 * @param string  
	 * @return void
	 * @access public;
	 */
	public function setTimestamp($timestamp) {
		 $this->timestamp = $timestamp;
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
	 * Constructs an Album object.  
	 * When called, this method will fill out each private member	
	 * of the Album object based on XML returned from Picasa's Atom feed.  It will also create
         * a Picasa_Image object for each image in the Album by passing XML for each image into 
         * the Picasa_Image constructor.	
	 *
	 * @param string $url   The URL of the Picasa query to retrieve the XML from.  See
	 *                      http://code.google.com/apis/picasaweb/gdata.html for information on
	 *                      how to format the URL.  If null, it is assumed that $albums param
	 *                      has been supplied.
	 * @param SimpleXMLElement $albums  XML for constructing the object.  If null, it is assumed that the 
	 *                                  URL to the Atom feed has been supplied.  If both are null, a
	 *                                  {@link Picasa_Exception} is thrown.
	 * @param array $contextArray       An array that can be passed to stream_context_create() to generate
	 *                                  a PHP context.  See 
	 *                                  {@link http://us2.php.net/manual/en/function.stream-context-create.php}
	 * @param boolean $useCache  You can decide not to cache a specific request by passing false here.  You may
	 *                           want to do this, for instance, if you're requesting a private feed.
	 * @throws {@link Picasa_Exception} If the XML suppled through either parameter does not contain valid XML.
	 */
	public function __construct ($url=null,SimpleXMLElement $albums=null, $contextArray=null, $useCache=true) {
		if ($url != null) {
			$xmldata = false;
			$context = null;

			Picasa_Logger::getLogger()->logIfEnabled('Request string: '.$url);

			if ($contextArray !== null) {
			    	$context = stream_context_create($contextArray);
			}
			if ($useCache === true) {
				$xmldata = Picasa_Cache::getCache()->getIfCached($url);
			}
			if ($xmldata === false) {
				Picasa_Logger::getLogger()->logIfEnabled("Not using cached entry for ".$url);
				$xmldata = @file_get_contents($url, false, $context);

				if ($useCache === true && $xmldata !== false) {
					Picasa_Logger::getLogger()->logIfEnabled("Refreshing cache entry for key ".$url);
					Picasa_Cache::getCache()->setInCache($url, $xmldata);
				}
			}
			if ($xmldata === false) {
				throw Picasa::getExceptionFromInvalidQuery($url, $contextArray);	
			}
			try {
				// Load the XML file into a SimpleXMLElement
				$albums = new SimpleXMLElement($xmldata);
			} catch (Exception $e) {
				throw new Picasa_Exception($e->getMessage(), null, $url);
			}

			// I'm not sure why there's a difference, but the icon is given in different ways
			// depending on if the document is just for an Album or if it's part of a larger document			
			$this->icon = $albums->icon;
		}
	    	// Whether or not the contextArray is null, it should be set.
		$this->contextArray = $contextArray;
		if ($albums != null) {
			$namespaces = $albums->getNamespaces(true);

			$this->picasaAuthor = new Picasa_Author($albums->author);

			$this->editLink = null;
			$link = $albums->link[0];
			$i = 0;
			while ($link != null) {
			    $attributes = $albums->link[$i]->attributes();
				if (strcmp($attributes["rel"],"edit")==0) {
					$this->editLink=$attributes["href"];
					break;
				} else if (strcmp($attributes["rel"],"alternate")==0) {
					$this->weblink=$attributes["href"];
				}
			    $i++;
			    $link = $albums->link[$i];
			}
			if (array_key_exists("gphoto", $namespaces)) {
				$gphoto_ns = $albums->children($namespaces["gphoto"]);
				$this->location = $gphoto_ns->location;
				$this->idnum = $gphoto_ns->id;
				$this->numphotos = $gphoto_ns->numphotos;
				$this->photosRemaining = $gphoto_ns->numphotosremaining;
				$this->bytesUsed = $gphoto_ns->bytesUsed;
				$this->commentingEnabled = $gphoto_ns->commentingEnabled;
				$this->numComments = $gphoto_ns->commentCount;
				$this->timestamp = $gphoto_ns->timestamp;

				// The picasaAuthor field must be set before this line is executed
				if ($this->picasaAuthor->getUser() == null || strcmp($this->picasaAuthor->getUser(), "") == 0) {
				    	$this->picasaAuthor->setUser($gphoto_ns->user);
				}
			}

			if (array_key_exists("media", $namespaces)) {
				$media_ns = $albums->children($namespaces["media"]);
				// As stated above, this is to account for the different placement of icon
				if ($url === null) {
					$thumbAtt = $media_ns->group->thumbnail->attributes();
					$this->icon = $thumbAtt["url"];
				}
			}

			if (array_key_exists("georss", $namespaces)) {
				$georss_ns = $albums->children($namespaces["georss"]);
				$gml_ns = $georss_ns->children($namespaces["gml"]);
				$this->gmlPosition = @$gml_ns->Point->pos;
			}

			$this->id = $albums->id;
			$this->title = $albums->title;
			$this->updated = $albums->updated;
			$this->published = $albums->published;
			$this->summary = $albums->summary;
			$this->rights = $albums->rights;
			$this->author = $albums->author->name;
			$this->subtitle = $albums->subtitle;
			$this->comments = null;

			$this->images = array();
			$i = 0;
			//Create a new Image object for each Image element
			foreach($albums->entry as $images) {
				$this->images[$i] = new Picasa_Image(null,$images);
				$i++;
			}

		}	
	}

	/**
	 * Constructs a textual representation of the current instantiation.
	 *
	 * @return string
	 */
	public function __toString() {
		$retstring = "
  [ TYPE:        Picasa_Album 
    AUTHOR:      ".$this->author."
    TITLE:       ".$this->title."
    SUBTITLE:    ".$this->subtitle."
    ICON:        ".$this->icon."
    ID:          ".$this->id."        
    WEBLINK:     ".$this->weblink."        
    PUBLISHED:   ".$this->published." 
    UPDATED:     ".$this->updated."  
    SUMMARY:     ".$this->summary." 
    RIGHTS:      ".$this->rights." 
    LOCATION:    ".$this->location."     
    IDNUM:       ".$this->idnum."     
    NUMPHOTOS:   ".$this->numphotos."   
    PICASAAUTHOR:".$this->picasaAuthor." 
    TAGS:        ".$this->tags."	      
    GMLPOSITION: ".$this->gmlPosition."
    PHOTOSREMAINING:".$this->photosRemaining."
    BYTESUSED:   ".$this->bytesUsed."
    COMMENTINGENABLED:".$this->commentingEnabled."
    NUMCOMMENTS: ".$this->numComments."
    EDITLINK:    ".$this->editLink."
    IMAGES: ";
		foreach ($this->images as $image) {
		    $retstring .= $image;
		}
		$retstring .= "
  ]";
		return $retstring;
	}
}
