<?php
/**
 * Represents an HTTP Error 400 Bad Request.  
 * This implies that there was something wrong with the
 * data that was transmitted to the host during a request and the host could not understand
 * the data being sent.
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
class Picasa_Exception_BadRequestException extends Picasa_Exception {
	public function __construct($message, $response=null, $url=null, $code=0) {
		parent::__construct($message, $response, $url, $code);
	}
}
