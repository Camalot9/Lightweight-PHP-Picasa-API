<?php

/**
 * This indicates that an HTTP Error 500 Internal Server Error has occured.  
 * An Internal Server Error means after the request was sent, an error occured on the host while processing the request.
 *
 * This is just a wrapper for the {@link Picasa_Exception} class.  It was created so that if the client
 * would like to display a customized message for this type of error, that would be possible.  It
 * doesn't have any unique fields or methods, it only inherits the ones in {@link Picasa_Exception}.
 *
 * @since Version 3.0
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @package Picasa_Exception
 */
class Picasa_Exception_InternalServerErrorException extends Picasa_Exception {
    	
	public function __construct($message, $response=null, $url=null, $code=0) {
		parent::__construct($message, $response, $url, $code);
	}
}
