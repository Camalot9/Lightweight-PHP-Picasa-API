<?php

include_once 'Picasa/Logger.php';

/**
 * A simple chaching mechanism to reduce the number of calls made to the Picasa service.
 * This is an easy way to cache into flat files the XML feed returned by Picasa for many API calls.
 * This class uses the Singleton pattern so that caching can be turned on once for the client's
 * entire application.
 * 
 * @author Cameron Hinkle
 * @version Version 1.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2007 Cameron Hinkle
 * @package Picasa
 * @since Version 3.3
 */
class Picasa_Cache {

	/**
	 * Default number of seconds a file will remain cached before it is refreshed.
	 * The value out of the box is 7200 (2 hours).  If you do not want to set it manually in your
	 * client application, feel free to change it here.
	 *
	 * @access private
	 * @var int
	 * @see function setCacheExpire()
	 */
	private static $DEFAULT_CACHE_EXPIRE = 7200;

	/**
	 * Default location on your server's file system that cache files are stored.
	 * Actually, this location is appended to PHP's include path.  This is done by default so a location
	 * is chosen that PHP definitely has access to.  You can change this is the class's constructor.
	 * It is highly recommended that you change this to whatever you want it to be if you do not
	 * want the out of the box value because it is risky to change it within your client code. 
	 *
	 * This path must be readable and writeable by php or caching will be disabled automatically.
	 *
	 * @access private
	 * @var string
	 */
	private static $DEFAULT_CACHE_PATH = 'picasa_api_cache';

	/**
	 * The caching instance, used for the Singleton pattern.
	 *
	 * @access private
	 * @var Picasa_Cache
	 */
	private static $instance=null;

	/**
	 * Flag to determine whether or not to cache files.
	 *
	 * @access private
	 * @var boolean
	 */
	private $enabled;

	/**
	 * The absolute path to store the cache files.  PHP must have write access to this path 
	 * or caching will be turned off.
	 *
	 * @access private
	 * @var string
	 */
	private $cachePath;
	
	/**
	 * The number of seconds after any file is cached that it should be refreshed.  If a cache
	 * key is accessed this number of seconds after it is initially set, the caching mechanism
	 * behaves as though it is not cached.
	 *
	 * @access private
	 * @var integer
	 */
	private $cacheExpire;

	/**
	 * @access public
	 * @return string
	 */
	public function getCacheExpire() {
		return $this->cacheExpire;
	}
	
	/**
	 * @access public
	 * @return string
	 */
	public function getCachePath() {
		return $this->cachePath;
	}
	
	/**
	 * @access public
	 * @param string $cacheExpire
	 * @return void 
	 */
	public function setCacheExpire($cacheExpire) {
		$this->cacheExpire = $cacheExpire;
	}

	/**
	 * @access public
	 * @param string $cacheExpire
	 * @return void 
	 */
	public function setCachePath($cachePath) {
		$this->cachePath = $cachePath;
	}
	
	/**
	 * Constructs a Cache object.  Declared as private for Singleton pattern so there is only one instance.
	 *
	 * @access private
	 * @param boolean $enabled	Determines whether or not to cache.
	 */
	private function __construct($enabled=false) {
		$this->enabled = $enabled;

		$this->setCacheExpire(Picasa_Cache::$DEFAULT_CACHE_EXPIRE);

		// Check for a slash at the end of the include path. If there isn't one, there will need
		// to be one appended before the cache directory.
		$slash = '';
		$incPath = get_include_path();
		if ($incPath[strlen($incPath)-1] !== '/') {
			$slash = '/';
		}

		// Sets the path that files get cached.  Uses the include path as a starting
		// directory but if that's not what you want, this would be the place to change it.
		$this->setCachePath(get_include_path().$slash.Picasa_Cache::$DEFAULT_CACHE_PATH.'/');
		if (file_exists($this->getCachePath()) === false) {
			if (!@mkdir($this->getCachePath())) {
				Picasa_Logger::getLogger()->logIfEnabled("Warning: Cache path did not exist and could not be created.  Perhaps you need to give PHP write access?  In the meantime, caching has been disabled.");
				$this->enabled = false;
			}
		}
		if (is_writable($this->getCachePath()) === false) {
				Picasa_Logger::getLogger()->logIfEnabled("Warning: Cache path exists but PHP cannot write to it.  Perhaps you need to give PHP write access?  In the meantime, caching has been disabled.");
			$this->enabled = false;
		}
			
	}

	/**
	 * Public method to get a Cache instance.  This is to ensure that only one instance is ever created.
	 *
	 * @access public
	 * @return Picasa_Cache 	The Cache instance.
	 */
	static public function getCache() {
		if(self::$instance==null){
			self::$instance=new Picasa_Cache(true);
		}
		return self::$instance;
	}

	/**
	 * Sets the flag that determines whether or not to cache.
	 *
	 * @access public
	 * @param boolean $enabled	The value to set the enabled flag to.  true will cause xml feeds to be cached,
	 *				false will cause xml feeds to always be downloaded fresh.  The default is true.
	 */  
	public function setEnabled($enabled=true) {
		$cache = self::getCache();
		$cache->enabled = $enabled;
		if ($enabled === true) {
			Picasa_Logger::getLogger()->logIfEnabled("Caching enabled.");
		} else {
			Picasa_Logger::getLogger()->logIfEnabled("Caching disabled.");
		}
	}

	/**
	 * Just checks the value of the enabled flag.  Could be true or false.
	 * 
	 * @access public
	 * @return boolean	The value of the enabled flag or false if it's not set.
	 */
	public function isEnabled() {
		$cache = self::getCache();

		// enabled could be uninitialized, so need to check for true
		if ($cache->enabled === true) {
			Picasa_Logger::getLogger()->logIfEnabled("Caching is enabled.");
			return true;
		} else {
			Picasa_Logger::getLogger()->logIfEnabled("Caching is disabled.");
			return false;
		}
	}

	/**
	 * Checks if the feed passed in is already cached.
	 *
	 * @access public
	 * @param string $feedUrl	The URL of the XML feed to cache.  Not the cache key itself.
	 * @return boolean		true if the feed is cached and the cache key hasn't expired, false otherwise.
	 */
	public function isCached($feedUrl) {
		if ($feedUrl === null)
		{
			return false;
		}
		$filename = $this->getCacheKey($feedUrl);
		Picasa_Logger::getLogger()->logIfEnabled("Checking if cache key ".$filename." exists in the cache.");
		return ($this->existsInCache($filename) && !$this->isExpired($filename));
	}

	/**
	 * Clears the cache.  If a path is supplied, just clears the key mapping to that path.  Otherwise, clears the entire cache.
	 *
	 * @access public
	 * @param string $pathname	The URL of the XML feed to clear from the cache.  If none is supplied, the entire cache is cleared.
	 * @return boolean	true if it successfully clears the requested part of the cache, false otherwise.
	 */
	public function clear($pathname=null) {
		$success = true;
		if ($pathname === null) {
			$dirhandle = opendir($this->getCachePath());
			while (($file = readdir($dirhandle)) !== false) {
				if (strcmp(".",$file) !== 0 && strcmp("..",$file) !== 0 && @unlink($this->getCachePath().$file) === false) {
					Picasa_Logger::getLogger()->logIfEnabled("Unable to remove cache item: ".$file);
					$success = false;
				} 
			}
			if ($success) {
				Picasa_Logger::getLogger()->logIfEnabled("Cache cleared.");
			} else {
				Picasa_Logger::getLogger()->logIfEnabled("One or more items in the cache could not be cleared.");
			}
		} else {
			$clearfile = $this->getCachePath().$this->getCacheKey($pathname);
			if (@unlink($clearfile)) {
				Picasa_Logger::getLogger()->logIfEnabled("Item successfully cleared from cache: ".$pathname);
				$success = true;
			} else {
				Picasa_Logger::getLogger()->logIfEnabled("Item could not be cleared from cache: ".$pathname);
				$success = false;
			}
		}
		return $success;
	}

	/**
	 * Gets the requested feed from the cache if it exists.  If it doesn't, returns false.
	 *
	 * @access public
	 * @param string $feedUrl	The URL of the XML feed to fetch from the cache.
	 * @return			The contents of the cached URL if the feed was cached.  false otherwise.
	 */
	public function getIfCached($feedUrl) {
		if ($feedUrl === null)
		{
			Picasa_Logger::getLogger()->logIfEnabled("Error: Null cache key passed to getIfCached.");
			return false;
		}
		if ($this->isCached($feedUrl) && $this->isEnabled() === true)
		{
			return $this->cacheGet($this->getCacheKey($feedUrl));
		}
		return false; 
	}	

	/**
	 * Caches the contents of a file.
	 *
	 * @access public
	 * @param string $filename	The full path of the contents being cached.
	 * @param string $contents	The contents to cache.
	 * @return boolean			true if the content was successfully cached, false otherwise.
	 */
	public function setInCache($filename, $contents) {
		if ($this->isEnabled() === false) {
			return false;
		}

		$formattedFilename = $this->getCacheKey($filename);
		if (is_writable($this->getCachePath()) && $contents !== null && strcmp($contents, "") !== 0) {
			$success = file_put_contents($this->getCachePath().$formattedFilename, $contents);
		} else {
			return false;
		}
	}

	/**
	 * Gets the contents at the specified cache key.  It will throw an error if the key is not in the cache.  It will also return
	 * the contents if the cache key has expired.  The existence and validity of the key needs to be checked before calling this.
	 *
	 * @access private
	 * @param string $key	The cache key to get the contents of.
	 * @return string			The value at the specified cache key.
	 */
	private function cacheGet($key) {
		return @file_get_contents($this->getCachePath().$key);
	}

	/**
	 * Converts the URL to the feed to cache into a cache key.
	 *
	 * @access private
	 * @param string $feedname	The URL to convert into a cache key.
	 * @return string			The valid cache key.
	 */
	private function getCacheKey($feedName) {
		//return str_replace('/','.',$feedName);
		//The characters that are escaped below are \ | ? *
		return preg_replace('([<>:"/\\\|\?\*])','.',$feedName);
	}

	/**
	 * Checks if a cache key has expired.
	 *
	 * @access private	
	 * @param string $key	The cache key to check the validity of.
	 * @return boolean		true if the cache key has passed its expiration time, false otherwise.
	 * @see $cacheExpire
	 */
	private function isExpired($key) {
		$modified = filemtime($this->getCachePath().$key);
		$diff = time() - $modified;
		if ($diff > $this->getCacheExpire()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks the mere existance (not expiration) of a cache key.
	 *
	 * @access private	
	 * @param string $key	The cache key to check the existance of.
	 * @return boolean		true if the cache key exists, false otherwise.
	 */
	private function existsInCache($key) {
		if (is_readable($this->getCachePath().$key))
		{
			return true;
		}
		return false; 
	}
}
?>
