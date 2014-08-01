<?php

/**
 * Indicates that the user's attempt to login using Client Login failed because he entered
 * the wrong username or password.  There is no way in Picasa to determine if the username or the password is incorrect,
 * just that one of them was.
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
class Picasa_Exception_InvalidUsernameOrPasswordException extends Picasa_Exception {
    	
	public function __construct($message, $response=null, $url=null, $code=0) {
		parent::__construct($message, $response, $url, $code);
	}
}
