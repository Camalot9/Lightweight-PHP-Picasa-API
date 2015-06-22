<?php

/**
 * A simple logging mechanism for displaying information while debugging a client application.
 * This class uses the Singleton pattern so that debugging can be turned on once for the entire
 * application.
 * 
 * @author Cameron Hinkle
 * @version Version 1.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @package Picasa
 * @since Version 3.2
 * @todo Add levels of logging and the ability to log to a file.
 */
class Picasa_Logger {

    	/**
	 * The logger instance, used for the Singleton pattern.
	 *
	 * @access private
	 * @var Picasa_Logger
	 */
	private static $instance=null;

    	/**
	 * Flag to determine whether or not to log statements.
	 *
	 * @access private
	 * @var boolean
	 */
	private $enabled;

	/**
	 * Constructs a Logger object.  Declared as private for Singleton pattern so there is only one instance.
	 *
	 * @access private
	 * @param boolean $enabled	Determines whether or not to log.
	 */
	private function __construct($enabled) {
		$this->enabled = $enabled;
	}

	/**
	 * Public method to get a logger instance.  This is to ensure that only one instance is ever created.
	 *
	 * @access public
	 * @return Picasa_Logger 	The Logger instance.
	 */
	static public function getLogger() {
		if(self::$instance==null){
			self::$instance=new Picasa_Logger(false);
		}
		return self::$instance;
	}

	/**
	 * Sets the flag that determines whether or not to print log messages.
	 *
	 * @access public
	 * @param boolean $enabled	The value to set the enabled flag to.  true will cause log messages to be printed,
	 *				false will cause log messages to be supressed.  The default is false.
	 */  
	public function setEnabled($enabled=false) {
		$logger = self::getLogger();
		$logger->enabled = $enabled;
	}

	/**
	 * Just checks the value of the enabled flag.  Could be true or false.  If true, log messages will be printed.  If
	 * false, log messages will be supressed.
	 *
	 * @access public
	 * @return boolean	The value of the enabled flag or false if it's not set.
	 */
	public function isEnabled() {
		$logger = self::getLogger();

		// enabled could be uninitialized, so need to check for true
		if ($logger->enabled === true) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Logs a message if logging is currently enabled.  If logging is not enabled, the message is supressed.
	 *
	 * @access public
	 * @param string $msg	The message to log.
	 * @return void
	 */
	public function logIfEnabled($msg) {
		if ($this->isEnabled()) {
			$this->log($msg);
		}
	}

	/**
	 * Prints the provided message to the screen followed by a line break.
	 * This is defined as protected so that client applications will use logIfEnabled().
	 *
	 * @access protected
	 * @param string $msg	The message to log.
	 * @return void
	 * @todo 		Make this method more robust, so that it can log to a file.
	 */
	protected function log($msg) {
		print($msg.PHP_EOL);
	}
}
?>
