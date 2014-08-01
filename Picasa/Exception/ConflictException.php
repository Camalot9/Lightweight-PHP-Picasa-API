<?php
/**
 * Represents an HTTP Error 409 Conflict.  
 * This implies that the request that was made conflicts with
 * a Picasa rule.  For instance, trying to modify an image but passing in the wrong albumid will
 * cause this error. Note that in the case of an Error 409, Picasa will sometimes provide the XML for
 * the conflicting object in place of an error message.  This will get set as the message in the Exception
 * so your client may want to watch for a ConflictException and not print the error message directly. 
 * 
 * This is just a wrapper for the {@link Picasa_Exception} class.  It was created so that if the client
 * would like to display a customized message for this type of error, that would be possible.  It
 * doesn't have any unique fields or methods, it only inherits the ones in {@link Picasa_Exception}.
 *
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @since Version 3.0
 * @package Picasa_Exception
 */
class Picasa_Exception_ConflictException extends Picasa_Exception {
	public function __construct($message, $response=null, $url=null, $code=0) {
		parent::__construct($message, $response, $url, $code);
	}
}
