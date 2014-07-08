<?php
/**
 * This exception indicates that an HTTP Error 404 File Not Found error has occured when requesting a file on a remote server.  
 * It could also indicate that a specified file on the local server could not be found, 
 * depending on the situation.
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
class Picasa_Exception_FileNotFoundException extends Picasa_Exception {
    	
	public function __construct($message, $response=null, $url=null, $code=0) {
		parent::__construct($message, $response, $url, $code);
	}
}
