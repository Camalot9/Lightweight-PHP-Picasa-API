<?php

require_once 'Picasa/Logger.php';

/**
 * Represents the simplest form of an image.  Contains attributes not specific to Picasa but global
 * to images of all types.  
 *
 * @package Picasa
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @since Version 3.2
 */

class Picasa_Thumbnail {
    	/**
	 * The width of the thumbnail.
	 * 
	 * @var array
	 * @access private
	 */
	private $width;


	/**
	 * The height of the thumbnail.
	 *
	 * @access private
	 * @var string
	 */
	private $height; 

	/**
	 * The location of the image.
	 *
	 * @access private
	 * @var string
	 */
	private $url;

	/**
	 * @access public
	 * @return string
	 */
	public function getWidth () {
		return $this->width;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getHeight () {
		return $this->height;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getUrl () {
		return $this->url;
	}

	/**
	 * @access public
	 * @param string
	 * @return void
	 */
	public function setWidth ($width) {
		$this->width = $width;
	}

	/**
	 * @access public
	 * @param string
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
	public function setUrl ($url) {
		$this->url = $url;
	}

	/**
	 * Constructs a Thumbnail object.
	 *
	 * @param string $url	The location of the image.
	 * @param int $width	The width of the image.
	 * @param int $height	The height of the image.
	 */
	public function __construct ($url, $width, $height) {
		$this->url = $url;
		$this->width = $width;
		$this->height = $height;
	}

	/**
	 * Constructs a textual representation of the current instantiation.
	 *
	 * @return string
	 */
	public function __toString() {
		$retstring = "
    [ TYPE:        Picasa_Thumbnail
      URL:         ".$this->url."
      WIDTH:       ".$this->width."
      HEIGHT:      ".$this->height."         
    ]";
		return $retstring;
	}

}
