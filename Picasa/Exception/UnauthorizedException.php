<?php

/**
 * Indicates that an Error 401 Unauthorized response was recieved from the host after a request.  
 * It also could indicate that an operation that requires authorization was attempted on an unauthorized
 * object, and the request was never sent for that reason.  In any case, it indicates that authorization
 * was required but not found. 
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
class Picasa_Exception_UnauthorizedException extends Picasa_Exception {
    	
	public function __construct($message, $response=null, $url=null, $code=0) {
		parent::__construct($message, $response, $url, $code);
	}
}
