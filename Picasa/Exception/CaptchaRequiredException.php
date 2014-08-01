<?php

/**
 * Indicates that Google issued a CAPTCHA challenge when the client was attempting to login a user.
 * Once this exception is caught, display the image from the {@link $captchaUrl} field to the user, as well as
 * an input field.  Instruct the user to type the letters that appear in the image into the input field.  Once
 * the user submits the letters, do the login attempt once again, supplying the value of {@link $captchaToken}
 * and the user's submitted text.
 *
 *
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @author Cameron Hinkle
 * @since Version 3.0
 * @package Picasa_Exception
 * @link http://code.google.com/support/bin/answer.py?answer=55815&topic=10954
 * @see Picasa::authorizeWithClientLogin()
 */
class Picasa_Exception_CaptchaRequiredException extends Picasa_Exception {

	/**
	 * The token supplied by Google, which is used by their servers to determine which image the captcha
	 * response is in response to.
	 *
	 * @var string
	 * @access private
	 */    
	private $captchaToken;

	/**
	 * The URL of the image that contains the challenge.
	 *
	 * @var string
	 * @access private
	 */    
	private $captchaUrl;

	/**
	 * The path of the CAPTCHA URL, without the server name at the beginning.  This field may be useless.
	 *
	 * @var string
	 * @access private
	 */    
	private $captchaPath;

	/**
	 * The email address of the user who was attempting to login when this CAPTCHA challenge was issued.  
	 * This is to make it easy to retry the login once the user has responded to the challenge. 
	 *
	 * @var string
	 * @access private
	 */    
	private $emailAddress;

	/**
	 * The password that the user entered when they recieved the challenge.  
	 * This is so that the user does
	 * not have to retype his password.
	 *
	 * @var string
	 * @access private
	 */    
	private $password;

	/**
	 * Getter field for private member.
	 *
	 * @return string
	 * @access public
	 */
	public function getCaptchaToken()
	{
	    	return $this->captchaToken;
	}

	/**
	 * Getter field for private member.
	 *
	 * @return string
	 * @access public
	 */
	public function getCaptchaPath()
	{
	    	return $this->captchaPath;
	}

	/**
	 * Getter field for private member.
	 *
	 * @return string
	 * @access public
	 */
	public function getCaptchaUrl()
	{
		return $this->captchaUrl;
	}

	/**
	 * Getter field for private member.
	 *
	 * @return string
	 * @access public
	 */
	public function getEmailAddress()
	{
		return $this->emailAddress;
	}

	/**
	 * Getter field for private member.
	 *
	 * @return string
	 * @access public
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * Constructs the exception.
	 *
	 * @access public
	 * @param string $message        The error message.
	 * @param string $emailAddress   The email address of the user who was attempting to login when this CAPTCHA challenge was issued.
	 * @param string $password       The password that the user entered when they recieved the challenge.
	 * @param string $captchaToken   The token supplied by Google.
	 * @param string $captchaUrl     The URL of the image that contains the challenge.
	 */
	public function __construct($message, $url, $emailAddress, $password, $captchaToken, $captchaUrl, $response, $code=0) {
	    	$this->emailAddress = $emailAddress;
		$this->password = $password;
		$this->captchaToken = $captchaToken;
		$this->captchaPath = $captchaUrl;
		$this->captchaUrl = 'http://www.google.com/accounts/'.$captchaUrl;
		parent::__construct($message, $response, $url, $code);
	}


	/**
	 * Constructs a textual representation of the current instantiation.
	 *
	 * @return string
	 */
	public function __toString() {
		$retstring = "
    [ TYPE:        Picasa_Exception_CaptchaRequiredException
      MESSAGE:         ".$this->getMessage()."
      EMAILADDRESS:    ".$this->emailAddress."
      PASSWORD:        ".$this->password."
      CAPTCHATOKEN:    ".$this->captchaToken."
      CAPTCHAPATH:     ".$this->captchaPath."
      CAPTCHAURL:      ".$this->captchaUrl."
      REQUESTURL:      ".$this->getUrl()."
      RESPONSEBUFFER:  ".$this->getResponse()."
    ]";
		return $retstring;
	}
    
}
