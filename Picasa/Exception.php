<?php

/**
 * Defines an exception originating within the Picasa API.  This is used as a skeleton
 * that other Picasa Exceptions will extend and is typically thrown when an unknwon
 * error occurs.  If the error type is known, usually an extended version of this class
 * will be thrown.
 *
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @package Picasa_Exception
 * @since Version 3.0
 * @author Cameron Hinkle
 */
class Picasa_Exception extends Exception {

    	/**
	 * If the exception originated from a request to another server, this field holds
	 * the entire contents of the response, including the headers.
	 *
	 * @var string
	 * @access private
	 */
	private $response;

    	/**
	 * If the exception originated from a request to another server, this field holds
	 * the URL that was requested. 
	 *
	 * @var string
	 * @access private
	 */
    	private $url;

	/**
	 * Getter method for $url field.
	 *
	 * @access public
	 * @return string
	 */
	public function getUrl() {
	    	return $this->url;
	}

	/**
	 * Getter method for $response field.
	 *
	 * @access public
	 * @return string
	 */
	public function getResponse() {
	    	return $this->response;
	}

	/**
	 * Constructs a Picasa_Exception object.
	 *
	 * @param string $message    The error message describing why the exception was thrown.
	 * @param string $response   If the exception originated from a request to another server, this field holds
	 *                           the entire contents of the response, including the headers.  Optional, the default
	 *                           is null.
	 * @param string $url        If the exception originated from a request to another server, this field holds
	 *                           the URL that was requested.  Optional, the default is null.
	 */
	public function __construct($message, $response=null, $url=null, $code = 0) {
		$this->url = $url;
	    	$this->response = $response;
		parent::__construct($message, $code);
	}

	/**
	 * Constructs a textual representation of the current instantiation.
	 *
	 * @return string
	 */
	public function __toString() {
		$retstring = "
    [ TYPE:            ".get_class()." 
      MESSAGE:         ".$this->getMessage()."
      REQUESTURL:      ".$this->url."
      RESPONSEBUFFER:  ".$this->response."
    ]
Backtrace:
";
		$backtrace = $this->getTrace();
		foreach ($backtrace as $line) {
			$retstring .= '    '.$line['class'].$line['type'].$line['function'].'() in '.$line['file'].' at line '.$line['line'].'
';
		}
		return $retstring;
	}
}
