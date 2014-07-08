<?php

require_once('Picasa/Comment.php');
require_once('Picasa/Thumbnail.php');
require_once('Picasa/Cache.php');
require_once('Picasa/Logger.php');

/**
 * Represents a single image in a Picasa account.  
 *
 * @package Picasa
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @since Version 1.0
 */

class Picasa_Image {
    	/**
	 * An array that can be passed to stream_create_context() to get a context.  
	 * This context array
	 * is used rather than just an auth token because the context array holds both the auth token
	 * and the type of authentication that was performed.  The context can be passed as a parameter
	 * when instantiating a {@link Picasa} object to make the instantiation authenticated.
	 *
	 * @var array
	 * @access private
	 */
	private $contextArray;


	/**
	 * The URL to the image's page on PicasaWeb.
	 *
	 * @access private
	 * @var string
	 */
	private $id; 

	/**
	 * The title or file name of the photo.
	 *
	 * @access private
	 * @var string
	 */
	private $title;

	/**
	 * The date that the image was last updated. 
	 *
	 * @access private
	 * @var string
	 */
	private $updated;

	/**
	 * A description of the image, entered by the account owner.
	 *
	 * @access private
	 * @var string
	 */
	private $description;

	/**
	 * A comma seperated list of keywords for the image (Deprecated, use $tags). 
	 *
	 * @access private
	 * @var string
	 */
	private $keywords;  

	/**
	 * A URL to the smallest thumbnail provided by Picasa by default. 
	 * 72px on the longest side by default. 
	 * Although this field is deprecated, it can be used for simplicity.  The smallest thumbnail will
	 * always be placed in this field, even if a custom thumbnail size is used.
	 *
	 * @access private
	 * @var string
	 * @deprecated Deprecated since Version 3.0.  Use $thumbUrlMap instead.
	 */
	private $smallThumb;

	/**
	 * The middle thumbnail provided by Picasa by default.  
	 * 144px on the longest side by default. 
	 * Even though this field is deprecated, it can be used for simplicity.  If two or more thumbnail sizes
	 * are requested, this field will contain a link to the second-largest one.  If only one thumbnail size
	 * is requested, this field will be null (so be careful).  By default, three thumbnails are requested
	 * so this field will be filled if no thumbnail size is specifically requested.
	 *
	 * @access private
	 * @var string
	 * @deprecated Deprecated since Version 3.0.  Use $thumbUrlMap instead.
	 */
	private $mediumThumb;   

	/**
	 * The largest thumbnail provided by Picasa by default.  
	 * 288px on the longest side by default. 
	 * Even though this field is deprecated, it can be used for simplicity.  If three or more thumbnail sizes
	 * are requested, this field will contain a link to the third-largest one.  If only one or two thumbnail sizes
	 * are requested, this field will be null (so be careful).  By default, three thumbnails are requested
	 * so this field will be filled if no thumbnail size is specifically requested.
	 *
	 * @access private
	 * @var string
	 * @deprecated Deprecated since Version 3.0.  Use $thumbUrlMap instead.
	 */
	private $largeThumb;   

	/**
	 * A mapping of thumbnail widths to the URLs for those thumbnails.  
	 * This field was introduced because multiple thumbnail
	 * sizes can be requested.  In order to keep track of which thumbnail URL is which size, this array was added.  In order to
	 * not have to create a specific class that holds the thumbnail's URL, width, and height, the $thumbHeightMap array was created
	 * to map thumbnail widths to thumbnail heights.  You can loop through this array and when you get a thumbnail you want using
	 * the width as a key, use the same key to get its height.
	 *
	 * This is not really necessary if you request three or fewer thumbnail sizes at a time and you don't need to know the size
	 * of them.  The deprecated fields $smallThumb, $mediumThumb, and $largeThumb will work just fine. 
	 *
	 * @access private
	 * @var array
	 * @deprecated Deprecated since Version 3.2.  Use $thumbnails instead.
	 */
	private $thumbUrlMap;

	/**
	 * A mapping of thumbnail widths to their respective heights.  
	 * Use the thumbnail's width as a key to get it's height.  The
	 * location of the thumbnail can be retrieved from the field {@link $thumbUrlMap} using, again, the width as a key.
	 *
	 * @access private
	 * @var array
	 * @see $thumbUrlMap
	 * @deprecated Deprecated since Version 3.2.  Use $thumbnails instead.
	 */
	private $thumbHeightMap;

	/**
	 * An array of {@link Picasa_Thumbnail} objects.
	 * The sizes and number of thumbnails in this array will vary depending on what was requested.
	 *
	 * @access private
	 * @var array
	 * @since Version 3.0
	 * @see $thumbUrlMap
	 */
	private $thumbnails;

	/**
	 * A downloadable link to the full size of the photo.  
	 * This URL cannot be seen inside a <img> HTML tag.  Instead, use
	 * thumbnails.  Though this field is deprecated, it can still be used for simplicity.  If only one size was requested
	 * (which is the default case), then this field will contain the URL to that image size.  If more than one size is 
	 * requested, this field will contain the URL of the smallest one. 
	 *
	 * @access private
	 * @var string
	 * @deprecated Deprecated since Version 3.0.  Use $contentUrlMap instead.
	 */
	private $content;     

	/**
	 * A map of image widths to their respective URLs.  
	 * This map mimicks the {@link $thumbUrlMap} field, except these URLs
	 * are not thumbnails and can't be used inside <img> HTML tags.  In practice, only one image size can be requested.  This
	 * field was created because users who are 
	 *
	 * @access private
	 * @var array
	 */
	private $contentUrlMap;

	/**
	 *
	 *
	 * @access private
	 * @var array
	 */
	private $contentHeightMap;

	/**
	 * A unique number assigned to the image by Picasa, unique across all Picasa images. 
	 *
	 * @access private
	 * @var string 
	 */
	private $idnum;  

	/**
	 * The original width of the image.  
	 *
	 * @access private
	 * @var int
	 */
	private $width; 

	/**
	 * The original height of the image.
	 *
	 * @access private
	 * @var int
	 */
	private $height; 

	/**
	 * The id number of the Album the image is in. 
	 *
	 * @access private
	 * @var int
	 */
	private $albumid;    

	/**
	 * The number of comments entered for the image. 
	 *
	 * @access private
	 * @var string
	 */
	private $commentCount;

	/**
	 * An array of strings, one for each keyword associated with the image. 
	 *
	 * @access private
	 * @var array
	 */
	private $tags;   

	/**
	 * An array of {@link Picasa_Comment} objects containing all the comments for the image. 
	 *
	 * @access private
	 * @var array
	 */
	private $comments;

	/**
	 *
	 *
	 * @access private
	 * @var {@link Picasa_Author} 
	 */
	private $author;

	/**
	 *
	 *
	 * @access private
	 * @var string
	 */
	private $albumTitle;

	/**
	 *
	 *
	 * @access private
	 * @var string
	 */
	private $imageType;

	/**
	 *
	 *
	 * @access private
	 * @var string
	 */
	private $albumDescription;

	/**
	 *
	 *
	 * @access private
	 * @var boolean 
	 */
	private $flash;	

	/**
	 *
	 *
	 * @access private
	 * @var float
	 */
	private $fstop;

	/**
	 *
	 *
	 * @access private
	 * @var string
	 */
	private $cameraMake;

	/**
	 *
	 *
	 * @access private
	 * @var string
	 */
	private $cameraModel;

	/**
	 *
	 *
	 * @access private
	 * @var float
	 */
	private $exposure;

	/**
	 *
	 *
	 * @access private
	 * @var float
	 */
	private $focalLength;

	/**
	 *
	 *
	 * @access private
	 * @var int
	 */
	private $iso;

	/**
	 *
	 *
	 * @access private
	 * @var int
	 */
	private $timeTaken;

	/**
	 *
	 *
	 * @access private
	 * @var int
	 */
	private $version;

	/**
	 *
	 *
	 * @access private
	 * @var boolean
	 */
	private $commentingEnabled;

	/**
	 * The number of miliseconds after January 1st, 1970 that the image was taken.
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $timestamp;

	/**
	 * The location that the image was taken.  
	 * The format is latitude and longitude, separated by a space.  This is often left blank,
	 * the {@link Picasa_Album::$location} field is more reliable.
	 *
	 * @var string
	 * @access private
	 * @since Version 3.0
	 */
	private $gmlPosition;

	/**
	 * The previous image in the image's current album.
	 *
	 * @var {@link Picasa_Image}
	 * @access private
	 * @since Version 3.0
	 */
	private $previous;

	/**
	 * The next image in the image's current album.
	 *
	 * @var {@link Picasa_Image}
	 * @access private
	 * @since Version 3.0
	 */
	private $next;

	/** 
	 * The URL to actual imagepage PicasaWeb.
	 *
	 * @access private
	 * @var string
	 */
	private $weblink;
 
	/**
	 * @access public
	 * @return string
	 */
	public function getId () {
		return $this->id;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getTitle () {
		return $this->title;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getUpdated () {
		return $this->updated;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getDescription () {
		return $this->description;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getKeywords () {
		return $this->keywords;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getSmallThumb () {
		return $this->smallThumb;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getMediumThumb () {
		return $this->mediumThumb;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getLargeThumb () {
		return $this->largeThumb;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getThumbUrlMap () {
		return $this->thumbUrlMap;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getThumbHeightMap () {
		return $this->thumbHeightMap;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getThumbnails () {
		return $this->thumbnails;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getContent () {
		return $this->content;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getContentUrlMap () {
		return $this->contentUrlMap;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getContentHeightMap () {
		return $this->contentHeightMap;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getIdnum () {
		return $this->idnum;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getWidth () {
		return $this->width;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getHeight () {
		return $this->height;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getAlbumid () {
		return $this->albumid;
	}
	
	/**
	 * @access public
	 * @return int
	 */
	public function getCommentCount () {
		return $this->commentCount;
	}

	/**
	 * Gets the comments for this image.  
	 * If the comments weren't supplied with the original image, this method will try to
	 * retrieve them again just to make sure.
	 *
	 * @access public
	 * @return array
	 */
	public function getComments () {
		    if (($this->commentCount === null || intval($this->commentCount) > 0) && $this->comments === null) {
			if ($this->author != null) {
				$pic = new Picasa(null, null, null, $this->contextArray);
			    	$user = $this->author->getUser();
				$thisImg = $pic->getImageById($user, $this->albumid, $this->idnum);
				$this->commentCount = $thisImg->getCommentCount();
				$this->comments = $thisImg->getComments();
			}
		}  
		return $this->comments;
	}

	/**
	 * @access public
	 * @return array
	 */
	public function getTags () {
		return $this->tags;
	}

	/**
	 * @access public
	 * @return Picasa_Author
	 */
	public function getAuthor () {	
		return $this->author;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getAlbumTitle () {	
		return $this->albumTitle;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getImageType () {	
		return $this->imageType;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getAlbumDescription () {	
		return $this->albumDescription;
	}

	/**
	 * @access public
	 * @return boolean
	 */
	public function getFlash () {	
		return $this->flash;	
	}

	/**
	 * @access public
	 * @return float
	 */
	public function getFstop () {	
		return $this->fstop;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getCameraMake () {	
		return $this->cameraMake;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getCameraModel () {	
		return $this->cameraModel;
	}

	/**
	 * @access public
	 * @return float
	 */
	public function getExposure () {	
		return $this->exposure;
	}

	/**
	 * @access public
	 * @return float
	 */
	public function getFocalLength () {	
		return $this->focalLength;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getIso () {	
		return $this->iso;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getTimeTaken () {	
		return $this->timeTaken;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getVersion () {	
		return $this->version;
	}

	/**
	 * @access public
	 * @return boolean
	 */
	public function getCommentingEnabled () {	
		return $this->commentingEnabled;
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
	public function getGmlPosition() {
		return $this->gmlPosition;
	}

	/**
	 * @return {@link Picasa_Image} 
	 * @access public
	 */
	public function getPrevious() {
		if ($this->previous === null) {
			$this->loadPreviousAndNext();
		}
		return $this->previous; 
	}

	/**
	 * @return {@link Picasa_Image} 
	 * @access public
	 */
	public function getNext() {
		if ($this->next === null) {
			$this->loadPreviousAndNext();
		}
		return $this->next; 
	}

	/**
	 * @return string 
	 * @access public
	 */
	public function getWeblink () {
		return $this->weblink;
	}

	/**
	 * Loads the private members $previous and $next with the previous and next
	 * images in their album.
	 *
	 * @return void
	 * @access private
	 */
	private function loadPreviousAndNext() {
		$albumid = $this->getAlbumid();
		$pic = new Picasa(null, null, null, $this->contextArray);
		$user = $this->author->getUser();
		$album = $pic->getAlbumById($user, $albumid);
		$images = $album->getImages();
		$count = count($images);
		$selectedId = $this->idnum;

		// Find the current image in its album
		for ($i=0; $i < $count && strcmp($images[$i]->getIdnum(),$selectedId) != 0; $i++);

		if ($i===0) {
			$this->previous = null;
		} else {
			$this->previous = $images[$i-1];
		}
		if ($i===$count) {
			$this->next = null;
		} else {
			$this->next = $images[$i+1];
		}
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setId ($id) {
		$this->id = $id;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setTitle ($title) {
		$this->title = $title;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setUpdated ($updated) {
		$this->updated = $updated;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setDescription ($description) {
		$this->description = $description;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setKeywords ($keywords) {
		$this->keywords = $keywords;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setSmallThumb ($smallThumb) {
		$this->smallThumb = $smallThumb;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setMediumThumb ($mediumThumb) {
		$this->mediumThumb = $mediumThumb;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setLargeThumb ($largeThumb) {
		$this->largeThumb = $largeThumb;
	}

	/**
	 * @access public
	 * @param array 
	 * @return void
	 */
	public function setThumbUrlMap ($thumbUrlMap) {
		$this->thumbUrlMap = $thumbUrlMap;
	}

	/**
	 * @access public
	 * @param array
	 * @return void
	 */
	public function setThumbHeightMap ($thumbHeightMap) {
		$this->thumbHeightMap = $thumbHeightMap;
	}

	/**
	 * @access public
	 * @param array
	 * @return void
	 */
	public function setThumbnails ($thumbnails) {
		$this->thumbnails = $thumbnails;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setContent ($content) {
		$this->content = $content;
	}

	/**
	 * @access public
	 * @param array 
	 * @return void
	 */
	public function setContentUrlMap ($contentUrlMap) {
		$this->contentUrlMap = $contentUrlMap;
	}

	/**
	 * @access public
	 * @param array
	 * @return void
	 */
	public function setContentHeightMap ($contentHeightMap) {
		$this->contentHeightMap = $contentHeightMap;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setIdnum ($idnum) {
		$this->idnum = $idnum;
	}

	/**
	 * @access public
	 * @param int
	 * @return void
	 */
	public function setWidth ($width) {
		$this->width = $width;
	}

	/**
	 * @access public
	 * @param int
	 * @return void
	 */
	public function setHeight ($height) {
		$this->height = $height;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setAlbumid ($albumid) {
		$this->albumid = $albumid;
	}

	/**
	 * @access public
	 * @param int
	 * @return void
	 */
	public function setCommentCount ($commentCount) {
		$this->commentCount = $commentCount;
	}

	/**
	 * @access public
	 * @param array
	 * @return void
	 */
	public function setComments ($comments) {
		$this->comments = $comments;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setTags ($tags) {
		$this->tags = $tags;
	}

	/**
	 * @access public
	 * @param Picasa_Author
	 * @return void
	 */
	public function setAuthor ($author) {	
		$this->author = $author;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setAlbumTitle ($albumTitle) {	
		$this->albumTitle = $albumTitle;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setImageType ($imageType) {	
		$this->imageType = $imageType;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setAlbumDescription ($albumDescription) {	
		$this->albumDescription = $albumDescription;
	}

	/**
	 * @access public
	 * @param boolean
	 * @return void
	 */
	public function setFlash ($flash) {	
		$this->flash = $flash;	
	}

	/**
	 * @access public
	 * @param float
	 * @return void
	 */
	public function setFstop ($fstop) {	
		$this->fstop = $fstop;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setCameraMake ($cameraMake) {	
		$this->cameraMake = $cameraMake;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setCameraModel ($cameraModel) {	
		$this->cameraModel = $cameraModel;
	}

	/**
	 * @access public
	 * @param float
	 * @return void
	 */
	public function setExposure ($exposure) {	
		$this->exposure = $exposure;
	}

	/**
	 * @access public
	 * @param float
	 * @return void
	 */
	public function setFocalLength ($focalLength) {	
		$this->focalLength = $focalLength;
	}

	/**
	 * @access public
	 * @param int
	 * @return void
	 */
	public function setIso ($iso) {	
		$this->iso = $iso;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setTimeTaken ($timeTaken) {	
		$this->timeTaken = $timeTaken;
	}

	/**
	 * @access public
	 * @param int
	 * @return void
	 */
	public function setVersion ($version) {	
		$this->version = $version;
	}

	/**
	 * @access public
	 * @param boolean
	 * @return void
	 */
	public function setCommentingEnabled ($commentingEnabled) {	
		$this->commentingEnabled = $commentingEnabled;
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
	 * @param string  
	 * @return void
	 * @access public;
	 */
	public function setGmlPosition($gmlPosition) {
		 $this->gmlPosition = $gmlPosition;
	}

	/**
	 * @param {@link Picasa_Image} 
	 * @return void
	 * @access public;
	 */
	public function setPrevious($previous) {
		 $this->previous = $previous;
	}

	/**
	 * @param {@link Picasa_Image} 
	 * @return void
	 * @access public;
	 */
	public function setNext($next) {
		 $this->next = $next;
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
	 * Constructs an Image object.
	 *
	 * @param string $url   URL of the query for the data that should be returned based on Picasa's API documentation.
	 *                      See {@link http://code.google.com/apis/picasaweb/gdata.html} for instructions on how to formulate
	 *                      the URL.  The URL parameter can be left blank, which will still produce a populated Image
	 *                      object as long as the $albums parameter contains XML from the Picasa Atom feed for an image.
	 * @param SimpleXMLElement $albums  XML describing a Picasa image.  This can be left blank as long as a URL is specified in the
	 *                                  url parameter that returns valid XML for a Picasa image.  If both are null, a 
	 *                                  {@link Picasa_Exception} is thrown.
	 * @param array $contextArray       An array that can be passed to stream_context_create() to generate
	 *                                  a PHP context.  See 
	 *                                  {@link http://us2.php.net/manual/en/function.stream-context-create.php}
	 * @param boolean $useCache  You can decide not to cache a specific request by passing false here.  You may
	 *                           want to do this, for instance, if you're requesting a private feed.
	 * @throws {@link Picasa_Exception} If the XML suppled through either parameter does not contain valid XML.
	 */

	public function __construct ($url=null, SimpleXMLElement $albums=null, $contextArray=null, $useCache=true) {
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
				Picasa_Logger::getLogger()->logIfEnabled('Cached copy not available, requesting image freshly for URL: '.$url);
				$xmldata = @file_get_contents($url, false, $context);
				if ($useCache === true && $xmldata !== false) {
					Picasa_Logger::getLogger()->logIfEnabled('Saving account to cache.');
					Picasa_Cache::getCache()->setInCache($url, $xmldata);
				}
			} else {
				Picasa_Logger::getLogger()->logIfEnabled('Image retreived from cache.');
			}
			if ($xmldata == false) {
				throw Picasa::getExceptionFromInvalidQuery($url, $contextArray);	
			}
			try {
				// Load the XML file into a SimpleXMLElement
				$albums = new SimpleXMLElement($xmldata);
			} catch (Exception $e) {
				throw new Picasa_Exception($e->getMessage(), null, $url);
			}
		}
		$this->contextArray = $contextArray;
		if ($albums != null) {

			foreach ($albums->link as $plink) {
				if ($plink['rel'] == 'alternate') {
					$this->weblink = $plink['href'];
				}
			}

			$namespaces = $albums->getNamespaces(true);
			if (array_key_exists("media", $namespaces)) {
				$media_ns = $albums->children($namespaces["media"]);
				$this->description = $media_ns->group->description;
				$this->keywords = $media_ns->group->keywords;

				$this->thumbUrlMap = array();
				$this->thumbHeightMap = array();

				$this->previous = null;
				$this->next = null;

			    	$i = 0;
				$this->thumbnails = array();
				$thumb = $media_ns->group->thumbnail[$i];
				while ($thumb != null) {
				    	$thumbAtt = $thumb->attributes();
					$width = "".$thumbAtt['width'];
					$height = $thumbAtt['height'];
					$url = $thumbAtt['url'];

					// These fields are deprecated but included for backwards compatibility
					$this->thumbUrlMap[$width] = $url;
					$this->thumbHeightMap[$width] = $height;

					$this->thumbnails[$i] = new Picasa_Thumbnail($url, $thumbAtt['width'], $thumbAtt['height']);
					$i++;
					$thumb = $media_ns->group->thumbnail[$i];
				}
	
				/* This is to support the previous implementation.  It may seem inefficiant to loop
				 * through twice, but typically there will be 1-3 iterations, so it is inconsequential. */
				$thumb = $media_ns->group->thumbnail[0];
				if ($thumb != null) {
				    $thumbAtt = $media_ns->group->thumbnail[0]->attributes();
				    $this->smallThumb = $thumbAtt['url'];
				} else {
				    $this->smallThumb = null;
				}
				$thumb = $media_ns->group->thumbnail[1];
				if ($thumb != null) {
				    $thumbAtt = $thumb->attributes();
				    $this->mediumThumb = $thumbAtt['url'];
				} else {
				    $this->mediumThumb = null;
				}
				$thumb = $media_ns->group->thumbnail[2];
				if ($thumb != null) {
				    $thumbAtt = $thumb->attributes();
				    $this->largeThumb = $thumbAtt['url'];
				} else {
				    $this->largeThumb = null;
				}

				$this->contentUrlMap = array();
				$this->contentHeightMap = array();
				$i = 0;
				$thumb = $media_ns->group->content[$i];
				while ($thumb != null) {
					$thumbAtt = $thumb->attributes();
					$width = "".$thumbAtt['width'];
					$height = $thumbAtt['height'];
					$url = $thumbAtt['url'];
					$this->contentUrlMap[$width] = $url;
					$this->contentHeightMap[$width] = $height;
					$i++;
					$thumb = $media_ns->group->content[$i];
				}
				$this->content = $thumbAtt['url'];
				$this->imageType = $thumbAtt['type'];


				// Pull and parse the tags 
				if ($media_ns->group->keywords != null || strcmp($media_ns->group->keywords,"") != 0) {

					// Make an array for to hold all of a photo's tags
					$this->tags = array();

					/* Tags are stored as a comma-delimited list.
					 * Tokenize the list on comma and strip it to get just the tag.
					 */
					$tok = strtok ($media_ns->group->keywords, ",");
					$i = 0;
					while ($tok != false) {
						// Set the tag in the array
						$this->tags[$i] = trim($tok);
						$tok = strtok(",");
						$i++;
					}
				} else {
				    	$this->tags = null;
				}
			} else {
				$this->description = null;
				$this->keywords = null;
				$this->smallThumb = null;
				$this->mediumThumb = null;
				$this->largeThumb = null;
				$this->content = null;
			}

			if (array_key_exists("gphoto", $namespaces)) {
				$gphoto_ns = $albums->children($namespaces["gphoto"]);
				$this->idnum = $gphoto_ns->id;
				$this->width = $gphoto_ns->width;
				$this->height = $gphoto_ns->height;
				$this->albumid = $gphoto_ns->albumid;
				$this->albumTitle = $gphoto_ns->albumtitle;
				$this->albumDescription = $gphoto_ns->albumdesc;
				$this->version = $gphoto_ns->version;
				$this->timestamp = $gphoto_ns->timestamp;
				$this->commentingEnabled = $gphoto_ns->commentingEnabled;
				if (strcmp($gphoto_ns->commentCount,"")==0 || $gphoto_ns->commentCount === null) {
				    	$this->commentCount = null;
				} else {
					$this->commentCount = intval($gphoto_ns->commentCount);
				}
			} else {
			    	$this->idnum = null;
				$this->width = null;
				$this->height = null;
				$this->albumid = null;
				$this->commentCount = null;
				$this->version = null;
				$this->timestamp = null;
			}

			if (array_key_exists("exif", $namespaces)) {
				$exif_ns = $albums->children($namespaces["exif"]);
				$this->flash = $exif_ns->tags->flash;	
				$this->fstop = $exif_ns->tags->fstop;
				$this->cameraMake = $exif_ns->tags->make;
				$this->cameraModel = $exif_ns->tags->model;
				$this->exposure = $exif_ns->tags->exposure;
				$this->focalLength = $exif_ns->tags->focallength;
				$this->iso = $exif_ns->tags->iso;
			} else {
				$this->flash = null; 
				$this->fstop = null;
				$this->cameraMake = null;    
				$this->cameraModel = null;
				$this->exposure = null;
				$this->focalLength = null;
				$this->iso = null;
			}


			if (array_key_exists("georss", $namespaces)) {
				$georss_ns = $albums->children($namespaces["georss"]);
				$gml_ns = @$georss_ns->children($namespaces["gml"]);
				if ($gml_ns !== null || $gml_ns !== false) {
					$this->gmlPosition = @$gml_ns->Point->pos;
				} else {
				   	$this->gmlPosition = null;
				}	
			} else {
			    	$this->gmlPosition = null;
			}

			// Set the basic attributes			
			$this->id = $albums->id;
			$this->title = $albums->title;
			$this->updated = $albums->updated;

			if ($albums->author != null && strcmp($albums->author, "") != 0) {
				$this->author = new Picasa_Author($albums->author);
			} else {
				$this->author = new Picasa_Author();
			}

			// The user is not a field in the XML for an image like it is for an album.  Thus, we parse.
			if ($this->author->getUser() == null || strcmp($this->author->getUser(),"") == 0) {
			    $startUser = strpos ($this->id, '/user/')+6;
				$endUser = strpos ($this->id, '/', $startUser);
				$this->author->setUser(substr($this->id, $startUser, ($endUser-$startUser)));
			}

			// If there are comments, retrieve them and put them into the comments field of the object
			if ($this->commentCount === null) {
				$this->comments = null;
			} else if ($this->commentCount === 0) {
			    	$this->comments = array();
			} else {
				$this->comments = array();
				$i = 0;

				// Grab each comment and make it into an object
				foreach ($albums->entry as $comment) {
					$this->comments[$i] = new Picasa_Comment($comment);
					$i++;
				}
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
    [ TYPE:        Picasa_Image
      TITLE:       ".$this->title."
      DESCRIPTION: ".$this->description."
      ID:          ".$this->id."         
      WEBLINK:     ".$this->weblink."         
      UPDATED:     ".$this->updated."      
      KEYWORDS:    ".$this->keywords."   
      SMALLTHUMB:  ".$this->smallThumb."   
      MEDIUMTHUMB: ".$this->mediumThumb."  
      LARGETHUMB:  ".$this->largeThumb."   
      THUMBURLMAP: 
      [  ";
	     	foreach ($this->thumbUrlMap as $width => $thumb) {
	    		$retstring .="
          ".$width."	=>  ".$thumb;
		}
		$retstring .= "
      ]
      THUMBHEIGHTMAP:
      [  ";
	     	foreach ($this->thumbHeightMap as $width => $thumb) {
	    		$retstring .="
          ".$width."	=>  ".$thumb;
		}
		$retstring .=" 
      ]
      CONTENT:     ".$this->content."     
      CONTENTURLMAP:
      [  ";
	     	foreach ($this->contentUrlMap as $width => $thumb) {
	    		$retstring .="
          ".$width."	=>  ".$thumb;
		}
		$retstring .= "
      ]
      CONTENTHEIGHTMAP:
      [  ";
	     	foreach ($this->contentHeightMap as $width => $thumb) {
	    		$retstring .="
          ".$width."	=>  ".$thumb;
		}
		$retstring .= "
      ]
      IDNUM:       ".$this->idnum."        
      WIDTH:       ".$this->width."        
      HEIGHT:      ".$this->height."       
      ALBUMID:     ".$this->albumid."      
      COMMENTCOUNT:".$this->commentCount." 
      COMMENTINGENABLED:".$this->commentingEnabled." 
      ALBUMTITLE:  ".$this->albumTitle."
      IMAGETYPE:   ".$this->imageType."
      ALBUMDESCRIPTION:".$this->albumDescription."
      FLASH:       ".$this->flash."	
      FSTOP:       ".$this->fstop."
      CAMERAMAKE:  ".$this->cameraMake."
      CAMERAMODEL: ".$this->cameraModel."
      EXPOSURE:    ".$this->exposure."
      FOCALLENGTH: ".$this->focalLength."
      ISO:         ".$this->iso."
      VERSION:     ".$this->version."
      TIMESTAMP:   ".$this->timestamp."
      GMLPOSITION: ".$this->gmlPosition."
      AUTHOR:      ".$this->author."
      TAGS:";

		// Tags are held in an array of strings off the Image
		if (is_array($this->tags)) {
			foreach ($this->tags as $tag) {
		    	$retstring .= "
      [ TYPE:        string";			    
			    $retstring .= "
        CONTENT:     ".$tag;
			$retstring .="
      ]";			    
			}
		}

		$retstring .= "
      COMMENTS:";
		if (is_array($this->comments)) {
			foreach ($this->comments as $comment) {
			    	$retstring .= $comment;
			}
		}

		$retstring .= "
    ]";
		return $retstring;
	}

}
