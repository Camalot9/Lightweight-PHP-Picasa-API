<?php

define('PICASA_API_BASE_DIR', __DIR__);

require_once PICASA_API_BASE_DIR . '/Picasa/Image.php';
require_once PICASA_API_BASE_DIR . '/Picasa/ImageCollection.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Album.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Account.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Comment.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Tag.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Author.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Logger.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception/CaptchaRequiredException.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception/UnauthorizedException.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception/BadRequestException.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception/FailedAuthorizationException.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception/FileNotFoundException.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception/InternalServerErrorException.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception/ConflictException.php';
require_once PICASA_API_BASE_DIR . '/Picasa/Exception/InvalidUsernameOrPasswordException.php';

/**
 * The primary class for interactions with Picasa.
 * This class was developed in order to let PHP Picasa developers stay away
 * from the low-level query building and request sending.  The Picasa class handles authorizations, including both AuthSub and
 * Client Login authorization establishment and management.  It also handles retrieving of image data from Picasa Web Albums,
 * storing the information in classes inside the Picasa subfolder.  Additionally, photo posting, updating, and deleting is all
 * made easy through the Picasa class.  Essentially just determine what verb you want to do ("get", "post", "delete", or "update"),
 * then figure out what noun you want to perform the verb on ("album", "image", "tag", or "comment").  If you put the two words together,
 * chances are good that you will find the method you want.  For instance, to *post* an *image*, you call {@link Picasa::postImage()}.
 * Provide the attributes of the image as parameters in the method and the photo will be created as a Picasa Web Album.
 *
 * In addition to utility methods, this class stores authorization information once an instance has been authorized.  For persistence, since
 * there is no caching mechanism with this API, this class has methods for storing and retrieving an authorization session from
 * the user's cookies.
 *
 * Because this class attempts to be so high level, not every single piece of Picasa's functionality options can be offered.  This API
 * should cover a huge percentage of desired functionality.  However, in case there is something offered by Picasa's Data API that
 * is not offered through this API, the class was made to be easily extendible.  For instance, if Picasa one day offers the ability
 * to upload videos and the latest version of this API does not offer video uploads, a PHP programmer could write a class that extends
 * the Picasa class and would have access to all the methods for managing authorizations and posting and retrieving data.  Additionally,
 * there are generic PHP methods such as {@link Picasa::do_request()} that are useful for performing operations that would normally
 * be handled by an installed PHP package.  These classes are also protected rather than private, so they can be used by classes
 * that extend this one, making your own extensions to this API easily doable.
 *
 * @package Picasa
 * @version Version 3.0
 * @license http://www.gnu.org/licenses/ GNU Public License Version 3
 * @copyright Copyright (c) 2008 Cameron Hinkle
 * @author Cameron Hinkle
 * @since Version 3.0
 */
class Picasa {

	/**
	 * The Url, without the protocol, of Picasa Web.
	 *
	 * @var string
	 * @static
	 * @access protected
	 */
	protected static $PICASA_URL =     'picasaweb.google.com';

	/**
	 * The base for a standard query.  All non-authenticated queries begin with this string.
	 *
	 * @var string
	 * @static
	 * @access protected
	 */
	protected static $BASE_QUERY_URL = 'http://picasaweb.google.com/data/feed/api';

	/**
	 * The base for queries that use the "entry" path instead of the "feed" path.  It's not clear
	 * what differentiates this path from the path in {@link Picasa::$BASE_QUERY_URL}, but the documentation
	 * makes it clear when each is necessary.
	 *
	 * @var string
	 * @static
	 * @access protected
	 */
	protected static $BASE_ENTRY_QUERY_URL = 'http://picasaweb.google.com/data/entry/api';

	/**
	 * The base for queries that use the "media" path instead of the "feed" path.  This path is required
	 * for updating the binary data in an image.
	 *
	 * @var string
	 * @static
	 * @access protected
	 */
	protected static $BASE_MEDIA_QUERY_URL = 'http://picasaweb.google.com/data/media/api';

	/**
	 * A number designated to represeting the Client Login method for Authorizing an object.
	 * There is no significance to the number assigned to this variable.
	 *
	 * @var int
	 * @static
	 * @access public
	 */
	public static $AUTH_TYPE_CLIENT_LOGIN = 2;

	/**
	 * A number designated to represeting the AuthSub method for Authorizing an object.
	 * There is no significance to the number assigned to this variable.
	 *
	 * @var string
	 * @static
	 * @access public
	 */
	public static $AUTH_TYPE_AUTH_SUB = 3;

	/**
	 * The title of the cookie to use when setting and retrieving an AuthSub token from a user's Cookies.
	 *
	 * @var string
	 * @static
	 * @access public
	 */
	public static $COOKIE_NAME_AUTH_SUB_TOKEN = 'auth_sub_token';

	/**
	 * The title of the cookie to use when setting and retrieving an Client Login token from a user's Cookies.
	 *
	 * @var string
	 * @static
	 * @access public
	 */
	public static $COOKIE_NAME_CLIENT_LOGIN_TOKEN = 'client_login_token';


	/**
	 * The email address or username that this instantiation is associated with.
	 * The field isn't currently used for anything because
	 * the username is never captured when using AuthSub authentication, and it's not required when executing requests that require
	 * authentication.
	 *
	 * @var string
	 * @access private
	 */
	private $emailAddress;

	/**
	 * A token supplied by Google after a successful attempt to authorize this object.
	 * The Auth Token is like a Golden Ticket
	 * for doing operations that require authorization.
	 *
	 * @var string
	 * @access private
	 */
	private $auth;

	/**
	 * An array that is in an acceptable format for the php function stream_context_create() to create a context.
	 * For an authorized instance, this array holds the HTTP header with the authorization information
	 * necessary to make authenticated requests.  It is useful to have because it holds both the authorization
	 * token as well as the authorization type in the same object.
	 *
	 * @link http://www.php.net/stream_context_create
	 * @var array
	 * @access private
	 */
	private $contextArray;

	/**
	 * The type of authorization that has been used to authenticate this instance.
	 * It must either be {@link Picasa::$AUTH_TYPE_AUTH_SUB} or {@link Picasa::$AUTH_TYPE_CLIENT_LOGIN}.  The auth type is
	 * required because the authorization header has to be formatted differently depending on what type
	 * of authorization was used.
	 *
	 * @link http://code.google.com/apis/accounts/docs/AuthForWebApps.html
	 * @link http://code.google.com/apis/accounts/docs/AuthForInstalledApps.html
	 * @var int
	 * @access private
	 */
	private $authType;

	/**
	 * The constructor allows the instantiation of an unauthorized or authorized Picasa object.
	 * All fields can be left blank
	 * for an unauthorized instantiation.  If an unauthorized instantiation is created, the object can later be authorized
	 * using {@link setAuthorizationInfo()}, {@link authorizeWithClientLogin()}, or {@link authorizeWithAuthSub()}.
	 *
	 * @access public
	 * @param string $authToken    An string representing an authorization issued by Google.  If a valid token is supplied,
	 *                             certain requests that are not allowed for unathorized objects are allowed.  Optional,
	 *                             the default is null.
	 * @param int $authType        An integer indicating what type of authorization was used.  Options are restricted to
	 *                             {@link Picasa::$AUTH_TYPE_CLIENT_LOGIN} and {@link Picasa::$AUTH_TYPE_AUTH_SUB}.  If
	 *                             the $authToken is not null, this field has to be supplied.  Optional, default is null.
	 * @param array $contextArray  The context array holds header information and can contain authorization information
	 *                             in the headers.  Optional, the default is null.
	 * @throws Picasa_Exception_FailedAuthorizationException If $authToken is supplied but not $authType, or
	 *                                                       if $authToken is not equal to {@link Picasa::$AUTH_TYPE_CLIENT_LOGIN}
	 *                                                       or {@link Picasa::$AUTH_TYPE_AUTH_SUB}.
	 */
	public function __construct($authToken=null, $authType=null, $contextArray=null) {
		if ($authToken != null) {
		    	if ($authType == null) {
			    	throw new Picasa_Exception_FailedAuthorizationException("You must declare an authorization type along with the token.");
			}
			if ($authType != Picasa::$AUTH_TYPE_AUTH_SUB && $authType != Picasa::$AUTH_TYPE_CLIENT_LOGIN) {
			    	throw new Picasa_Exception_FailedAuthorizationException("Invalid authorization type was supplied.");
			}
			$this->setAuthorizationInfo($authToken, $authType);
		} else if ($contextArray != null) {
		    	$this->contextArray = $contextArray;
		} else {
		    	$this->contextArray = $this->constructContextArray(false);
			$this->auth=null;
		}
	}


	/**
	 * Sets the private fields in the object with authorization information that is passed in.
	 * The fields are accessed when requests that require authorization are executed.
	 *
	 * @access public
	 * @param string $authToken An authorization token returned by Picasa when a successful authentication request is executed.
	 * @param string $authType  A string indicating which type of authorization was requested.  Options are "ClientLogin"
	 *                          and "AuthSub".  ClientLogin allows authorization directly with a username and password
	 *                          sent by the server, whereas AuthSub requires that the user be redirected to a login page hosted by Google.
	 * @return void
	 */
	public function setAuthorizationInfo ($authToken, $authType) {
		$this->auth = $authToken;
		$this->authType = $authType;
		$this->contextArray = $this->constructContextArray(true);
	}

	/**
	 * Getter method for the $auth private field.
	 *
	 * @access public
	 * @return string   The auth token supplied by executing a successful authentication request to Google.
	 *                  The same field is used regardless of the type of authorization requested.
	 */
	public function getAuthToken() {
	    return $this->auth;
	}

	/**
	 * Fetches the type of authorization this instantiation is configured with.
	 *
	 * @access public
	 * @return int     Possible return values are {@link Picasa::$AUTH_TYPE_AUTH_SUB}, {@link Picasa:$AUTH_TYPE_CLIENT_LOGIN},
	 *                 and null if this object is not authenticated.
	 */
	public function getAuthType() {
	    return $this->authType;
	}

	/**
	 * Fetches the context array for this object.
	 * The context array is defined at all times, whether or not the object is authenticated.  However, when it is
	 * authenticated, it becomes possible to include the authorization token in the array.
	 *
	 * @access public
	 * @return array
	 */
	public function getContextArray() {
	    return $this->contextArray;
	}

	/**
	 * Turns a context array into a resource, which can then be used in PHP methods such as {@link file_get_contents()} and {@link fopen()}.
	 *
	 * @access public
	 * @return resource A resource representing the context stored in the {@link $contextArray} private member.
	 * @link http://www.php.net/stream_context_create
	 */
	public function getContext() {
		return stream_context_create($this->contextArray);
	}

	/**
	 * Determines whether the instantiated Picasa object has been authenticated.
	 * In the case of a AuthSub authentication, the validity of the authentication is actually tested.  In the case of ClientLogin
	 * authentication, it just checks for the existence of the auth token; there is no way in Picasa's API
	 * to test the validity of a ClientLogin authentication.  If the instantiation has not been authenticated,
	 * this method will look for an auth token in the user's cookie and attempt to authenticate the object
	 * automatically, in which case true will be returned.
	 *
	 * @access public
	 * @return boolean   true if this instantiation is authenticated, false otherwise.
	 */
	public function isAuthenticated() {
		if ($this->authType === Picasa::$AUTH_TYPE_CLIENT_LOGIN) {
			return true;
		} else if ($this->authType === null) {
		    	return false;
		} else {
		    	try {
				return $this->isAuthorizationValid();
			} catch (Picasa_Exception $e) {
			    	return false;
			}
		}
	}

	/**
	 * Clears authentication fields in the object and sets the header information back to the default, non-autenticated version.
	 * Also clear the authorization out of the user's cookie.  It essentially logs them out.
	 *
	 * @access public
	 * @return void
	 */
	public function clearAuthentication() {
		if (array_key_exists(Picasa::$COOKIE_NAME_AUTH_SUB_TOKEN, $_COOKIE)) {
			@setcookie(Picasa::$COOKIE_NAME_AUTH_SUB_TOKEN, "");
		}
		if (array_key_exists(Picasa::$COOKIE_NAME_CLIENT_LOGIN_TOKEN, $_COOKIE)) {
			@setcookie(Picasa::$COOKIE_NAME_CLIENT_LOGIN_TOKEN, "");
		}

	    	$this->auth = null;
		$this->emailAddress = null;
		$this->contextArray = $this->constructContextArray(false);
		$this->authType = null;
	}

	/**
	 * Authorizes a Google email address through the Client Login method, described in Google's documentation
	 * ({@link http://code.google.com/apis/accounts/docs/AuthForInstalledApps.html}).  This allows special functions such
	 * as posting images and comments.
	 *
	 * On a successful authorization, true is returned and the $auth private member is set to the authorization string
	 * returned by Google.  If the authorization attempt fails, false is returned, and the $error private member is set
	 * to the error code returned by Google.  Possible error codes can be found at the URL listed above.
	 *
	 * @param string $emailAddress   The email address of the account that is going to be authorized.  Can also just be the user's Google
	 *                               username.
	 * @param string $password       The account's password, required for authorization.
	 * @param string $source         The source, which is a three part string.  This string should take the form:
	 *                               "companyName-applicationName-versionID".  Optional, the default is null.
	 * @param string $loginToken     The token returned by Google when the CAPTCHA challenge was requested.  Use
	 *                               {@link Picasa_Exception_CaptchaRequiredException::getCaptchaToken()} to get this value.  If the client
	 *                               is not responding to a Captcha challenge, leave this null.  Optional, the default is null.
	 * @param string $loginCaptcha   The text input by the user representing the CAPTCHA challenge presented to them.  If the client
	 *                               is not responding to a Captcha challenge, leave this null.  Optional, the default is null.
	 * @param boolean $saveAuthorizationToCookie  If this is true, the authorization token returned from Google is stored in the users'
	 *                                            browser cookie so that it can be retrieved later and the user can be logged in without
	 *                                            have to enter his email address and password (assuming the cookie and token both
	 *                                            do not expire.  false will only store the token in the current instance, which is not
	 *                                            persistent and disapears as soon as the page loads.  Optional, the default is true.
	 * @param int $expires           The number of seconds after the cookie is set that it should expire.  If $saveAuthorizationToCookie
	 *                               is false, this parameter is ignored.  Optional, the default is 2,592,000 seconds (30 days).
	 * @param string $service        The service that the account is being authorized for.  Each service has a different service
	 *                               code.  The code for Picasa is lh2, which is the default.
	 * @return string                The authorization token returned by Google.
	 * @throws Picasa_Exception_CaptchaRequiredException             If the response from Google indicates that a CAPTCHA challenge is required.
	 * @throws Picasa_Exception_InvalidUsernameOrPasswordException   If either the username or password that was supplied was invalid.
	 * @throws Picasa_Exception      A more generic exception if a different type of error occurs.
	 * @link http://code.google.com/apis/accounts/docs/AuthForInstalledApps.html
	 */
	public function authorizeWithClientLogin($emailAddress, $password, $source=null, $loginToken=null, $loginCaptcha=null, $saveAuthorizationToCookie=true, $expires=2592000, $service="lh2")
	{
		$this->emailAddress = $emailAddress;
		Picasa_Logger::getLogger()->logIfEnabled("Authenticating for ".$emailAddress);
	    	if ($source == null) {
		    	$source = $this->emailAddress."-UsingLightweightPicasaAPI-3.0";
		}

		$host="www.google.com";
		$path="/accounts/ClientLogin";
		$data="Email=".urlencode($emailAddress)."&Passwd=".urlencode($password)."&service=".urlencode($service)."&source=".urlencode($source);

		if ($loginToken != null && $loginCaptcha != null) {
		    	$data .= "&logintoken=".urlencode($loginToken)."&logincaptcha=".urlencode($loginCaptcha);
		}
		$buf = "";
		try {
			$buf = Picasa::do_request($host, $path, $data, "POST", null, "application/x-www-form-urlencoded", "ssl://", 443);
		} catch (Picasa_Exception $e) {
		    	$buf = $e->getResponse();
			$errorValue = Picasa::getResponseValue($buf,"Error");

			// A CAPTCHA may be required here, so pull the necessary information out of the response
			if (strcmp($errorValue,"CaptchaRequired") == 0) {
				$url = Picasa::getResponseValue($buf,"Url");
				$token = Picasa::getResponseValue($buf,"CaptchaToken");
				$captchaUrl = Picasa::getResponseValue($buf,"CaptchaUrl");

				throw new Picasa_Exception_CaptchaRequiredException("A CAPTCHA is required.", $e->getUrl(), $emailAddress, $password, $token, $captchaUrl, $buf);
			} else if (strcmp($errorValue,"BadAuthentication") == 0) {
			    	throw new Picasa_Exception_InvalidUsernameOrPasswordException("Username or password was invalid.");
			} else {
		   		throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
			}
		}
		$authString = Picasa::getResponseValue($buf,"Auth");
		$this->setAuthorizationInfo($authString, Picasa::$AUTH_TYPE_CLIENT_LOGIN);
		if ($saveAuthorizationToCookie) {
			$this->saveAuthToCookie($expires);
	    	}
		return $authString;
	}


	/**
	 * Authorizes the current object with Google's AuthSub authorization method, described in Google's documentation
	 * ({@link http://code.google.com/apis/accounts/docs/AuthForWebApps.html}).  With this method, the user has to
	 * have been redirected to a Google login page ({@link redirectToLoginPage()}) and granted access to your application
	 * to access their private data.  This method should be called on the page that the user is directed to after he
	 * logs in.  This method will always convert the token recieved from a single use token to a session token (for
	 * your convenience).
	 *
	 * This method will return the authorization token returned from Google.  However, the current object is automatically
	 * authenticated when this method executes successfully and that token is automatically saved in the {@link Picasa:$auth}
	 * field.  The returned token is probably not useful in many cases, but there wasn't anything better to return.
	 *
	 * @access public
	 * @param string $token   An authorization token supplied by Picasa in a URL parameter called "token"
	 *                        after the user logs in through the Google-hosted login page.  Optional, the default
	 *                        is null.  If null is passed, the token is taken from the $_GET superglobal.
	 * @param boolean $saveAuthorizationToCookie If true, the returned authentication token is saved to the
	 *                                           user's cookie.  If false, just stores the token in the $auth
	 *                                           field of the current object. Optional, default is true.
	 * @param int $expires    The number of seconds after the current time that the cookie should remain in
	 *                        the user's browser for.  Optional, default is 2592000 (30 days).
	 * @return string         The authorization token returned from Google.
	 * @see redirectToLoginPage()
	 * @link http://code.google.com/apis/accounts/docs/AuthForWebApps.html
	 */
	public function authorizeWithAuthSub ($token=null, $saveAuthorizationToCookie=true, $expires=2592000) {
		if ($token == null || strcmp($token, "") == 0 || strcmp($token, "null") == 0) {
			if (array_key_exists("token", $_GET)) {
		    		$token = @$_GET['token'];
			} else {
	    			throw new Picasa_Exception_FailedAuthorizationException("No token was found.  The token must either be passed to this method or accessible from the URL as a parameter.");
			}
		}
		$this->setAuthorizationInfo($token, Picasa::$AUTH_TYPE_AUTH_SUB);
		$sessionToken = null;
		try {
			$sessionToken = $this->convertFromSingleUseToSessionToken();
		} catch (Picasa_Exception $e) {
		    	throw new Picasa_Exception_FailedAuthorizationException($e->getMessage());
		}
		if ($saveAuthorizationToCookie) {
			$this->saveAuthToCookie($expires);
		}
		return $sessionToken;
	}



	/**
	 * Checks if an AuthSub authorization is valid by requesting the information from Picasa.
	 * This method can only be used
	 * on AuthSub authorizations; Picasa does not currently support this feature for Client Login authentications.
	 *
	 * @access public
	 * @return boolean           true if the authorization is valid, false if not.
	 * @throws Picasa_Exception  If the authorization is of type Client Login.
	 */
	public function isAuthorizationValid() {
		if ($this->authType == Picasa::$AUTH_TYPE_CLIENT_LOGIN) {
			throw new Picasa_Exception("This method only applicable for AuthSub authorizations.");
		}
		$host = 'www.google.com';
		$path = '/accounts/AuthSubTokenInfo';
		$header = array(1 => $this->getAuthHeader());
		try {
			Picasa::do_request($host, $path, null, "GET", $header, "application/x-www-form-urlencoded", 'ssl://', 443);
		} catch (Picasa_Exception $e) {
		    	// If there was an exception, we can assume that the authorization is not valid.
			return false;
		}
		return true;
	}


	/**
	 * When authorizing with AuthSub, Picasa initially issues a single-use token, which can only be used once.
	 * This method will convert that single use token into a session token, which will remain valid for a very long time.
	 *
	 * @access public
	 * @return string                    The authorization token returned from Google.
	 * @throws {@link Picasa_Exception}  If something was wrong with the request to Picasa.  In this case the token will not
	 *                                   be converted.  An exception subclass that is specific to the error encountered will
	 *                                   be thrown, so the client can catch the individual subclasses and respond accordingly.
	 */
	public function convertFromSingleUseToSessionToken() {
	    	$host='www.google.com';
		$path='/accounts/AuthSubSessionToken';
		$header = array( 1 => $this->getAuthHeader());
		$authString = null;
		try {
			$buf = Picasa::do_request($host, $path, null, "GET", $header, "application/x-www-form-urlencoded", 'ssl://', 443);
			$authString = Picasa::getResponseValue($buf,"Token");
			$this->setAuthorizationInfo($authString, Picasa::$AUTH_TYPE_AUTH_SUB);
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		return $authString;
	}




	/**
	 * If the current instantiation is authorized using the "AuthSub" method, this actively invalidates the token.
	 *
	 * @access public
	 * @return boolean true if the authorization was successfully deactivated.  false if it was not.
	 * @throws Picasa_Exception If the current instantiation is not authorized with AuthSub.
	 */
	public function deauthorize () {
	    	// Deauthorizations only available with AuthSub
	    	if ($this->authType == Picasa::$AUTH_TYPE_CLIENT_LOGIN) {
		    	throw new Picasa_Exception("Deauthorization only available with AuthSub type authorizations.");
		}

		$host = 'www.google.com';
		$path = '/accounts/AuthSubRevokeToken';
		$header = array (1 => $this->getAuthHeader());
		try {
			Picasa::do_request($host, $path, null, "GET", $header, "application/x-www-form-urlencoded", 'ssl://', 443);
		    	$this->clearAuthentication();
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		return true;
	}

	/**
	 * Saves the authentication token that was returned from Google to the user's browser in a cookie.
	 * This way it can be accessed later and if it's still valid, the user does not need to be required to log in again.  Since this deals
	 * with cookies, no output can go to the browser before this method is called or it will fail.
	 *
	 * @access protected
	 * @param int $expires    The number of seconds after the current time that the cookie should remain in the user's
	 *                        browser for.  Optional, default is2592000 (30 days).
	 * @return boolean        The result of the call to PHP's {@see setcookie()} function.
	 * @link http://us2.php.net/manual/en/function.setcookie.php
	 */
	protected function saveAuthToCookie($expires=2592000) {
	    	$cookieName = Picasa::$COOKIE_NAME_AUTH_SUB_TOKEN;
	    	if ($this->authType == Picasa::$AUTH_TYPE_CLIENT_LOGIN) {
			$cookieName = Picasa::$COOKIE_NAME_CLIENT_LOGIN_TOKEN;
		}
		return @setcookie($cookieName, $this->auth, time()+$expires);
	}

	/**
	 * Redirects the user to Google's AuthSub login page so that they can login to access restricted functions.
	 * The URL in the $next parameter must be registered with Google.  At the URL specified there, the "token" value must be pulled from
	 * the request and passed to {@see Picasa::authorizeWithAuthSub()} for the authorization to be complete.
	 *
	 * @access public
	 * @static
	 * @param string $next The URL that the user should be redirected to after visiting the login page.  This URL must be
	 *                     registered with Google in advance.  See
	 *                     {@link http://code.google.com/apis/accounts/docs/RegistrationForWebAppsAuto.html}.
	 * @param int $session Must be either 1 or 0.  This indicates if the login should be transferable to a session
	 *                     token, which is valid for a long time, wherease the default token is only useable once.
	 *                     1 to enable session tokens, 0 to disable.
	 * @return void
	 */
	public static  function redirectToLoginPage($next, $session=1) {
	    	$url = Picasa::getUrlToLoginPage($next, $session);
		header("Location: $url");
	}


	/**
	 * Constructs the URL that the user should be redirected to in order to be authorized with the AuthSub method.
	 * The URL is a page hosted by Google that will allow the user to login to his Picasa Account.  When the user returns to the client
	 * domain (specified in the $next parameter), a token supplied, appended to the URL.  Retrieving that token (using
	 * PHP's "$_GET" superglobal; the key is "token") and pass it to {@link authorizeWithAuthSub} to enable requests for the user
	 * that were previously disabled, such as posting photos and accessing private albums.
	 *
	 * @access public
	 * @static
	 * @param string $next The URL that the user should be redirected to once they have logged into their account.
	 * @param int $session Must be either 1 or 0.  This indicates if the login should be transferable to a session
	 *                     token, which is valid for a long time, wherease the default token is only useable once.
	 *                     1 to enable session tokens, 0 to disable.
	 * @return string      The URL that the user should be redirected to in order to log in to their Picasa account.
	 */
	public static function getUrlToLoginPage ($next, $session=1) {
		$url = 'https://www.google.com/accounts/AuthSubRequest?next='.urlencode($next).'&scope='.urlencode(Picasa::$BASE_QUERY_URL).'&session='.$session;
		return $url;
	}

	/**
	 * Retrieves a list of Picasa Albums contained within a {@link Picasa_Account} object based on the criteria supplied.
	 * Passing null for any of the parameters will leave it up to Picasa to define the default values.
	 *
	 * @access public
	 * @param string $username The username on the account to get the albums for.
	 * @param int $maxResults  The maximum number of results to return.  Optional, the default is null.
	 * @param int $startIndex  The first element number with the search results to return.  Useful for pagination.  Optional,
	 *                         the default is null.
	 * @param string $visibility Restrict the search to albums with the access rights specified here.  Options are "public",
	 *                           "private", and "all".  Authorization is required for "private" and "all" options.  Optional,
	 *                           default is "public".
	 * @param int $thumbsize   Comma-delimited list of thumbnail sizes to fetch.  Options are 32, 48, 64, 72, 144, 160, 200,
	 *                         288, 320, 400, 512, 576, 640, 720, 800.  URLs for the corresponding thumbnails are stored in
	 *                         the $thumbUrlMap of the {@link Picasa_Image} class.  Optional, default is null.  The default
	 *                         value will let Picasa choose the thumbnail sizes, which are stored in the $smallThumb,
	 *                         $mediumThumb, and $largeThumb fields of the {@link Picasa_Image} class.
	 * @param int $imgmax      Size of image to return in the $content field of the {@link Picasa_Image} field.  Options are
	 *                         32, 48, 64, 72, 144, 160, 200, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280,
	 *                         1440, 1600.  Only values of 800 and less are displayable within a <a> HTML tag, all other
	 *                         values can only be downloaded directly through the user's browser.  Optional, default value is
	 *                         null.  The default value will let Picasa determine the size to return, which will not be useable
	 *                         within a <a> tag.
	 * @return Picasa_Account  An Account including all albums within the specified user's account.
	 * @throws {@link Picasa_Exception}        If the request was somehow invalid.  This could mean that the requested object
	 *                                         does not have permission to retrieve the feed or the parameters supplied are not
	 *                                         allowed by Picasa, or a number of other possible errors.  A subclass of
	 *                                         {@link Picasa_Exception} will be thrown, which will indicate specifically what
	 *                                         the problem was with the request.
	 * @link http://code.google.com/apis/picasaweb/reference.html#Parameters
	 */
	public function getAlbumsByUsername($username, $maxResults=null, $startIndex=null, $visibility="public", $thumbsize=null, $imgmax=null) {
		$query = Picasa::$BASE_QUERY_URL.'/user/'.$username.'?kind=album';
		$query.=Picasa::buildQueryParams($maxResults, $startIndex, null, null, $visibility, $thumbsize, $imgmax);
		$account = null;

		$useCache = false;
		if (strcmp($visibility,"public") === 0) {
			$useCache = true;
		}

		try {
			Picasa_Logger::getLogger()->logIfEnabled("Fetching albums for user ".$username);
			$account = new Picasa_Account($query, null, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $account;
	}

	/**
	 * Retrieves all images meeting the supplied parameters.
	 * Passing null for any of the parameters will leave it up to Picasa to define the default values.
	 *
	 * @access public
	 * @param string $username The username on the account to get the images for.  Optional, default is null.
	 * @param int $maxResults  The maximum number of results to return.  Optional, the default is null.
	 * @param int $startIndex  The first element number with the search results to return.  Useful for pagination.
	 *                         Optional, the default is null.
	 * @param string $keywords Space-delimited list of keywords to search for.  Title and description are included
	 *                         in the search.  Optional, default is an empty string.
	 * @param string $tags     Space-delimited list of tags to search for.  Only images with all tags in the list are
	 *                         included in the search results.  Optional, default is empty string.
	 * @param string $visibility  Restrict the search to images with the access rights specified here.  Options are
	 *                            "public", "private", and "all".  Authorization is required for "private" and "all" options.
	 *                            Optional, default is "public".
	 * @param int $thumbsize   Comma-delimited list of thumbnail sizes to fetch.  Options are 32, 48, 64, 72, 144, 160,
	 *                         200, 288, 320, 400, 512, 576, 640, 720, 800.  URLs for the corresponding thumbnails are stored
	 *                         in the $thumbUrlMap of the {@link Picasa_Image} class.  Optional, default is null.  The default
	 *                         value will let Picasa choose the thumbnail sizes, which are stored in the $smallThumb, $mediumThumb,
	 *                         and $largeThumb fields of the {@link Picasa_Image} class.
	 * @param int $imgmax      Size of image to return in the $content field of the {@link Picasa_Image} field.  Options are 32,
	 *                         48, 64, 72, 144, 160, 200, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440,
	 *                         1600.  Only values of 800 and less are displayable within a <a> HTML tag, all other values can
	 *                         only be downloaded directly through the user's browser.  Optional, default value is null.  The
	 *                         default value will let Picasa determine the size to return, which will not be useable within a <a> tag.
	 * @param string $sortDescending Whether or not to sort the items returned by date added descending.
	 * @param string $boundingBox	Searches for images tagged as having been taken within a set of four coordinates. The
	 *				coordinates should be in the order west, south, east, north
	 * @param string $location	Searches for photos tagged as having been taken at a named location.  For instance "London".
	 * @return Picasa_ImageCollection          An object holding meta information about the requested feed, as well as an array of
	 *                                         {@link Picasa_Image} objects with each object that meets the supplied parameters.
	 * @throws {@link Picasa_Exception}        If the request was somehow invalid.  This could mean that the requested object
	 *                                         does not have permission to retrieve the feed or the parameters supplied are not
	 *                                         allowed by Picasa, or a number of other possible errors.  A subclass of
	 *                                         {@link Picasa_Exception} will be thrown, which will indicate specifically what
	 *                                         the problem was with the request.
	 * @link http://code.google.com/apis/picasaweb/reference.html#Parameters
	 */
	public function getImages($username=null, $maxResults=null, $startIndex=null, $keywords=null, $tags=null, $visibility=null, $thumbsize=null, $imgmax=null, $sortDescending=true, $boundingBox=null, $location=null) {
	    	$query = Picasa::$BASE_QUERY_URL;
		if ($username==null) {
		    $query .= '/all';
		} else {
		    $query .= '/user/'.$username;
		}
		$query .= '?kind=photo'.Picasa::buildQueryParams($maxResults, $startIndex, $keywords, $tags, $visibility, $thumbsize, $imgmax, null, $sortDescending, $boundingBox, $location);
		$images = null;

		$useCache = false;
		if (strcmp($visibility,"public") === 0) {
			$useCache = true;
		}

		try {
			$images = new Picasa_ImageCollection($query, null, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $images;
	}

	/**
	 * Retrieves the Picasa web album with the specified id.
	 * The album will contain all images that meet the specified criteria and is in the specified album.
	 * Passing null for any of the parameters will leave it up to Picasa to define the default values.
	 *
	 * @access public
	 * @param string $username The username on the account that the album is in.
	 * @param string $albumid  The id number of the desired album.
	 * @param int $maxResults  The maximum number of results to return.  Optional, the default is null.
	 * @param int $startIndex  The first element number with the search results to return.  Useful for pagination.
	 *                         Optional, the default is null.
	 * @param string $keywords Space-delimited list of keywords to search for.  Title and description are included in
	 *                         the search.  Optional, default is an empty string.
	 * @param string $tags     Space-delimited list of tags to search for.  Only images with all tags in the list are
	 *                         included in the search results.  Optional, default is empty string.
	 * @param int $thumbsize   Comma-delimited list of thumbnail sizes to fetch.  Options are 32, 48, 64, 72, 144, 160, 200,
	 *                         288, 320, 400, 512, 576, 640, 720, 800.  URLs for the corresponding thumbnails are stored in the
	 *                         $thumbUrlMap of the {@link Picasa_Image} class.  Optional, default is null.  The default value
	 *                         will let Picasa choose the thumbnail sizes, which are stored in the $smallThumb, $mediumThumb,
	 *                         and $largeThumb fields of the {@link Picasa_Image} class.
	 * @param int $imgmax      Size of image to return in the $content field of the {@link Picasa_Image} field.  Options are 32,
	 *                         48, 64, 72, 144, 160, 200, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440,
	 *                         1600.  Only values of 800 and less are displayable within a <a> HTML tag, all other values can
	 *                         only be downloaded directly through the user's browser.  Optional, default value is null.  The
	 *                         default value will let Picasa determine the size to return, which will not be useable within a <a> tag.
	 * @return Picasa_Album    An object representing the album with the supplied id.  Only photos in the album that meet
	 *                                  the parameters passed into this method will be included in the album.  This way, you can
	 *                                  (for isntance) search for tags and keywords within an album.
	 * @throws {@link Picasa_Exception} If the request was somehow invalid.  This could mean that the requested object
	 *                                  does not have permission to retrieve the feed or the parameters supplied are not
	 *                                  allowed by Picasa, or a number of other possible errors.  A subclass of
	 *                                  {@link Picasa_Exception} will be thrown, which will indicate specifically what
	 *                                  the problem was with the request.
	 * @link http://code.google.com/apis/picasaweb/reference.html#Parameters
	 */
	public function getAlbumById($username, $albumid, $maxResults=null, $startIndex=null, $keywords=null, $tags=null, $thumbsize=null, $imgmax=null) {
		$query = Picasa::$BASE_QUERY_URL . '/user/'.$username . '/albumid/'.$albumid;
		$query.='?kind=photo'.Picasa::buildQueryParams($maxResults, $startIndex, $keywords, $tags, null, $thumbsize, $imgmax);
		$album = null;

		// See if the instance is authenticated and don't use the cache if it is just in case
		// Use a quick way to test for authentication because it would take too long to validate
		$useCache = false;
		if ($this->auth === null) {
			$useCache = true;
		}

		try {
			Picasa_Logger::getLogger()->logIfEnabled("Fetching album ".$albumid." for user ".$username);
			$album = new Picasa_Album($query, null, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $album;
	}

	/**
	 * Retrieves an Album as an Entry as opposed to a Feed.
	 * Picasa's data api makes a distinction between the two.  The
	 * two different types will sometimes contain different fields.  This method should only be used if you are sure that the
	 * field you need is not accessible through a feed, only through an entry.  It is declared protected so that client code
	 * cannot call it directly, only extensions of the Picasa class.
	 *
	 * @access protected
	 * @param string $username The username on the account that the album is in.
	 * @param string $albumid  The id number of the desired album.
	 * @throws {@link Picasa_Exception}        If the request was somehow invalid.  This could mean that the requested object
	 *                                         does not have permission to retrieve the feed or the parameters supplied are not
	 *                                         allowed by Picasa, or a number of other possible errors.  A subclass of
	 *                                         {@link Picasa_Exception} will be thrown, which will indicate specifically what
	 *                                         the problem was with the request.
	 * @link http://code.google.com/apis/picasaweb/reference.html#Parameters
	 */
	protected function getAlbumByIdAsEntry($username, $albumid) {
		$query = Picasa::$BASE_ENTRY_QUERY_URL . '/user/'.$username . '/albumid/'.$albumid;
		$album = null;

		$useCache = true;
		if ($this->auth !== null) {
			$useCache = false;
		}

		try {
			$album = new Picasa_Album($query, null, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $album;
	}

	/**
	 * Retrieves the image with the given id.
	 *
	 * @access public
	 * @todo                   Refactor to use buildQueryParams()
	 * @param string $username The username on the account that the image is in.
	 * @param string $albumid  The id number of the album that the image is located in.
	 * @param string $imageid  The id number of the desired image.
	 * @param int $thumbsize   Comma-delimited list of thumbnail sizes to fetch.  Options are 32, 48, 64, 72, 144, 160, 200,
	 *                         288, 320, 400, 512, 576, 640, 720, 800.  URLs for the corresponding thumbnails are stored in the
	 *                         $thumbUrlMap of the {@link Picasa_Image} class.  Optional, default is null.  The default value
	 *                         will let Picasa choose the thumbnail sizes, which are stored in the $smallThumb, $mediumThumb,
	 *                         and $largeThumb fields of the {@link Picasa_Image} class.
	 * @param int $imgmax      Size of image to return in the $content field of the {@link Picasa_Image} field.  Options are 32,
	 *                         48, 64, 72, 144, 160, 200, 288, 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440,
	 *                         1600.  Only values of 800 and less are displayable within a <a> HTML tag, all other values can
	 *                         only be downloaded directly through the user's browser.  Optional, default value is null.  The
	 *                         default value will let Picasa determine the size to return, which will not be useable within a <a> tag.
	 * @return Picasa_Image    The requested image.
	 * @throws {@link Picasa_Exception} If the request was somehow invalid.  For instance, if
	 *                                  the image or album do not exist.
	 */
	public function getImageById($username, $albumid, $imageid, $thumbsize=null, $imgmax=null) {
		$image = null;
	    	$params = '';
		if ($thumbsize !== null) {
			$params .= '?thumbsize='.$thumbsize;
			if ($imgmax !== null) {
		    		$params .= '&imgmax='.$imgmax;
			}
		} else if ($imgmax !== null) {
		    	$params .= '?imgmax='.$imgmax;
		}

		$useCache = true;
		if ($this->auth !== null) {
			$useCache = false;
		}

	    	try {
			Picasa_Logger::getLogger()->logIfEnabled("Fetching image ".$imageid." for user ".$username);
			$image = new Picasa_Image(Picasa::$BASE_QUERY_URL.'/user/'.$username.'/albumid/'.$albumid.'/photoid/'.$imageid.$params, null, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $image;
	}


	/**
	 * Retrieves the image with the given id as an entry as opposed to a feed.
	 * Picasa's data api makes a distinction between the two.  The
	 * two different types will sometimes contain different fields.  This method should only be used if you are sure that the
	 * field you need is not accessible through a feed, only through an entry.  It is declared protected so that client code
	 * cannot call it directly, only extensions of the Picasa class.
	 *
	 * @access protected
	 * @param string $username The username on the account that the image is in.
	 * @param string $albumid  The id number of the album that the image is located in.
	 * @param string $imageid  The id number of the desired image.
	 * @return Picasa_Image    The requested image.
	 * @throws {@link Picasa_Exception} If the request was somehow invalid.  For instance, if
	 *                                  the image or album do not exist.
	 */
	protected function getImageByIdAsEntry($username, $albumid, $imageid) {
	    	$image = null;

		$useCache = true;
		if ($this->auth !== null) {
			$useCache = false;
		}

	    	try {
			$image = new Picasa_Image(Picasa::$BASE_ENTRY_QUERY_URL.'/user/'.$username.'/albumid/'.$albumid.'/photoid/'.$imageid, null, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $image;
	}

	/**
	 * Retrieves tags that meet the supplied parameters.
	 * Notice that this method will not retrieve tags specific to one
	 * image.  To get tags by image, get an entire image object (using, for instance, {@link getImageById()}),
	 * and then call {@link Picasa_Image::getTags()} on that image.
	 * Passing null for any of the parameters will leave it up to Picasa to define the default values.
	 *
	 * @access public
	 * @param string $username     The username on the account to get the tags out of.
	 * @param string $albumid      The id number of the album that the requested tags are in.  Optional, the default is null.
	 * @param int $maxResults      The maximum number of results to return.  Optional, the default is null.
	 * @param int $startIndex      The first element number with the search results to return.  Useful for pagination.  Optional,
	 *                             the default is null.
	 * @param string $visibility   Restrict the search to tags in images with the access rights specified here.
	 *                             Options are "public", "private", and "all".  Authorization is required for "private" and
	 *                             "all" options.  Optional, default is "public".
	 * @return array               An array of {@link Picasa_Tag} objects, one for each tag in the requested feed.
	 * @throws {@link Picasa_Exception}        If the request was somehow invalid.  This could mean that the requested object
	 *                                         does not have permission to retrieve the feed or the parameters supplied are not
	 *                                         allowed by Picasa, or a number of other possible errors.  A subclass of
	 *                                         {@link Picasa_Exception} will be thrown, which will indicate specifically what
	 *                                         the problem was with the request.
	 */
	public function getTagsByUsername($username, $albumid=null, $maxResults=null, $startIndex=null, $visibility="public") {
	    	$query = Picasa::$BASE_QUERY_URL.'/user/'.$username;
		if ($albumid != null) {
		    $query.='/albumid/'.$albumid;
		}
		$query.='?kind=tag'.Picasa::buildQueryParams($maxResults, $startIndex, null, null, $visibility, null, null);
		$tags = null;

		$useCache = false;
		if (strcmp($visibility,"public") === 0) {
			$useCache = true;
		}

	    	try {
			$tags = Picasa_Tag::getTagArray($query, null, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $tags;
	}


	/**
	 * Retrieves the comment with the given id.
	 *
	 * @access public
	 * @param string $username   The username on the account that the comment is in.
	 * @param string $albumid    The id number of the album that the comment is located in.
	 * @param string $imageid    The id number of the image that the comment is in.
	 * @param string $commentid  The id number of the requested comment.
	 * @return Picasa_Image      The requested comment.
	 * @throws {@link Picasa_Exception} If the request was somehow invalid.  For instance, if
	 *                                  the image or album or comment do not exist.
	 */
	public function getCommentById($username, $albumid, $imageid, $commentid) {
	    	$comment = null;

		$useCache = true;
		if ($this->auth !== null) {
			$useCache = false;
		}

		try {
		    	// Comments have to be retrieved with the entry url
			$comment = new Picasa_Comment(null, Picasa::$BASE_ENTRY_QUERY_URL.'/user/'.$username.'/albumid/'.$albumid.'/photoid/'.$imageid.'/commentid/'.$commentid, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $comment;
	}


	/**
	 * Gets all comments that meet the criteria specified in the parameters.
	 * The client can request images specific to
	 * just a user or to a user and an album.  To get comments specific to an image, use {@link getImageById()} and call
	 * {@link Picasa_Image::getComments()} on the returned object.
	 * Passing null for any of the parameters will leave it up to Picasa to define the default values.
	 *
	 * @access public
	 * @param string $username    The username on the account to get the comments out of.  It is not possible to get
	 *                            comments without specifying the username, so this field is required.
	 * @param string $albumid     The id number of the album that the desired comments are in.  Optional, the default
	 *                            is null.
	 * @param int $maxResults     The maximum number of results to return.  Optional, the default is null.
	 * @param int $startIndex     The first element number with the search results to return.  Useful for pagination.
	 *                            Optional, the default is null.
	 * @param string $visibility  Restrict the search to comments in images with the access rights specified here.
	 *                            Options are "public", "private", and "all".  Authorization is required for "private"
	 *                            and "all" options.  Optional, default is "public".
	 * @return array              An array of {@link Picasa_Comment} objects, one for each comment in the requested feed.
	 * @throws {@link Picasa_Exception}  If something was wrong with the requested feed.  A specific subclass of
	 *                                   {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                   encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                   itself will be thrown.
	 */
	public function getCommentsByUsername($username, $albumid=null, $maxResults=null, $startIndex=null, $visibility="public") {
	    	$query = Picasa::$BASE_QUERY_URL.'/user/'.$username;
		if ($albumid != null) {
		    $query.='/albumid/'.$albumid;
		}
		$query.='?kind=comment'.Picasa::buildQueryParams($maxResults, $startIndex, null, null, $visibility, null, null);
		$comments = null;

		$useCache = false;
		if (strcmp($visibility,"public") === 0) {
			$useCache = true;
		}

		try {
			$comments = Picasa_Comment::getCommentArray($query, null, $this->contextArray, $useCache);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $comments;
	}


	/**
	 * Retrieves contacts of the user specified in the parameter.
	 * A contact is a Picasa user who the specified user
	 * has declared as a "Favorite".  Should return an empty array unless the current Picasa instantiation is authorized.
	 *
	 * @access public
	 * @param string $username           The username on the account to get the contacts from.
	 * @return array                     An array of {@link Picasa_Author} objects, one for each of the user's contacts.
	 * @throws {@link Picasa_Exception}  If something was wrong with the requested feed.  A specific subclass of
	 *                                   {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                   encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                   itself will be thrown.
	 */
	public function getContactsByUsername($username) {
	    	$author = null;
	    	try {
	    		$author = Picasa_Author::getAuthorArray(Picasa::$BASE_QUERY_URL.'/user/'.$username.'/contacts?kind=user', null, $this->contextArray);
		} catch (Picasa_Exception $e) {
			throw $e;
		}
		return $author;
	}


	/**
	 * Post a new album to Picasa Web Albums.
	 *
	 * @access public
	 * @param string $username  The username on the account to post the album to.
	 * @param string $title     The title to assign to this album.
	 * @param string $summary   A summary of the contents of the album.  Optional, the default is an empty string.
	 * @param string $rights    The access rights to assign to the album.  Options are "public" and "private".  Optional,
	 *                          the default is "public".
	 * @param string $commentingEnabled   Identifies whether or not other users should be allowed to post comments to images
	 *                                    in the new album.  Optional, the default is "true".
	 * @param string $location  The location that the images in the album were taken.  Optional, the default is an empty string.
	 * @param string $timestamp The number of miliseconds after the Unix epoch (January 1, 1970) that the photos in the album were
	 *                          taken.  Notice that the PHP time() functions returns the number of seconds since the epoch, so
	 *                          that number has to be multiplied by 1000 to be used for this parameter.  Optional, the default is null.
	 *                          If null is passed here, the timestamp will be set to the current time.
	 * @param string $icon      The image that appears as the album cover.  Optional, the default is null.
	 * @param string $gmlPosition         The location in the world that this image was taken.  The format is latitude and longitude,
	 *                                    separated by a space.  Optional, the default is null.
	 * @return Picasa_Album     The album that was posted to Picasa.
	 * @throws {@link Picasa_Exception}  If something was wrong with the post to Picasa.  A specific subclass of
	 *                                   {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                   encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                   itself will be thrown.
	 */
	public function postAlbum ($username, $title, $summary="", $rights="public", $commentingEnabled="true", $location="", $timestamp=null, $icon=null, $gmlPosition=null) {
	    	if ($timestamp == null) {
		    	$timestamp = time() * 1000;
		}
		$data = Picasa::constructAlbumXML($title, $summary, $icon, $rights, $commentingEnabled, $location, $timestamp, $gmlPosition);
		$path = '/data/feed/api/user/'.$username;
		$host = Picasa::$PICASA_URL;
		$header = array(1 => $this->getAuthHeader());

		try {
			$albumBuffer = Picasa::do_request($host,$path,$data, "POST", $header, "application/atom+xml");
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		//Picasa will return the image XML.  Parse the id field out and create an image out of it.
		$startBracketPos = strpos($albumBuffer, "/albumid/") + 9;
		$endBracketPos = strpos($albumBuffer, "/", $startBracketPos);
		$id = substr($albumBuffer, $startBracketPos, $endBracketPos-$startBracketPos);

		try {
			$uploadedAlbum = $this->getAlbumById($username,$id);
		} catch (Picasa_Exception $e) {
			throw new Picasa_Exception("The image was successfully uploaded, but then the following error was encountered: ".$e->getMessage(), $e->getResponse(), $e->getUrl());
		}
		return $uploadedAlbum;
	}


	/**
	 * Posts an image to a Picasa Web Album.
	 *
	 * @access public
	 * @param string $username            The username on the account to post the image to.
	 * @param string $albumid             The id number of the album to post the image to.
	 * @param string $locationOnDisk      The path to the image on the local file system or network.  Although this parameter
	 *                                    has "OnDisk" in the name, you can specify a URL.
	 * @param string $type                The type of image that is being uploaded.  Picasa currently accepts "image/bmp",
	 *                                    "image/gif", "image/jpeg", and "image/png".
	 * @param string $title               The title that the image will be given.
	 * @param string $summary             A summary of what is in the image.  Optional, the default is an empty string.
	 * @param string $keywords            A comma-delimited list of keywords associated with the image.  Optional, the default is empty string.
	 * @param string $commentingEnabled   Set to true if other users should be able to comment on the image and false
	 *                                    if they shouldn't.  Optional, the default is true.
	 * @param string $timestamp           The number of miliseconds after the Unix epoch (January 1st, 1970) that the image was taken (roughly).
	 *                                    Optional, the default is null, which will set $timestamp to the current time.
	 * @param string $gmlPosition         The location in the world that this image was taken.  The format is latitude and longitude,
	 *                                    separated by a space.  Optional, the default is null.
	 * @return Picasa_Image               The newly uploaded image.
	 * @throws {@link Picasa_Exception}   If something was wrong with the post to Picasa.  A specific subclass of
	 *                                    {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                    encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                    itself will be thrown.
	 * @link http://code.google.com/support/bin/answer.py?answer=63316&topic=10973
	 */
	public function postImage ($username, $albumid, $locationOnDisk, $type, $title, $summary="", $keywords="", $commentingEnabled="true", $timestamp=null, $gmlPosition=null) {
	    	$fileContents = @file_get_contents($locationOnDisk);
		if ($fileContents === false) {
		    	throw new Picasa_Exception_FileNotFoundException("The specified file could not be found.");
		}
		$size = strlen($fileContents);

		// PHP counts seconds past the epoch and Picasa counts milliseconds, so we multiply
		if ($timestamp === null) {
		    	$timestamp = time() * 1000;
		}
		$metaXML = Picasa::constructImageXML($title,$summary,$keywords,$commentingEnabled,$timestamp,$gmlPosition);
		$data = "\r\n
Media multipart posting
--END_OF_PART
Content-Type: application/atom+xml

$metaXML
--END_OF_PART
Content-Type: $type

$fileContents
--END_OF_PART--";
		$host = Picasa::$PICASA_URL;
		$path = "/data/feed/api/user/$username/albumid/$albumid";
		$authHeader = array (1 => $this->getAuthHeader(),
		    	             2 => "MIME-version: 1.0\r\n");
		$imgBuffer = "";
		try {
		    $imgBuffer = $uploadedString = Picasa::do_request($host, $path, $data, "POST", $authHeader, "multipart/related; boundary=\"END_OF_PART\"");
		} catch (Picasa_Exception $e) {
		   	throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		//Picasa will return the image XML.  Parse the id field out and create an image out of it.
		$startBracketPos = strpos($imgBuffer, "/photoid/") + 9;
		$endBracketPos = strpos($imgBuffer, "/", $startBracketPos);
		$id = substr($imgBuffer, $startBracketPos, $endBracketPos-$startBracketPos);

		try {
			$uploadedImage = $this->getImageById($username,$albumid,$id);
		} catch (Picasa_Exception $e) {
			throw new Picasa_Exception("The image was successfully uploaded, but then the following error was encountered: ".$e->getMessage(), $e->getResponse(), $e->getUrl());
		}
		return $uploadedImage;
	}

	/**
	 * Add a tag to an existing Picasa image.  A tag is a single word that describes all or part of the photo.
	 *
	 * @access public
	 * @param string $username            The username of the account that the image to tag is in.
	 * @param string $albumid             The album id of the album that the image to tag is in.
	 * @param string $imageid             The image id of the image that the tag is for.
	 * @param string $tag                 The tag to post.
	 * @return Picasa_Image               The image that the tag was posted to.
	 * @throws {@link Picasa_Exception}   If there was a problem sending the comment.  For instance, if the
	 *                                    object is not authenticated or the image does not exist.
	 */
	public function postTag($username, $albumid, $imageid, $tag) {
		$data = "<entry xmlns='http://www.w3.org/2005/Atom'>
	<title>$tag</title>
	<category scheme=\"http://schemas.google.com/g/2005#kind\" term=\"http://schemas.google.com/photos/2007#tag\"/>
</entry>";
		$host = Picasa::$PICASA_URL;
		$path = "/data/feed/api/user/$username/albumid/$albumid/photoid/$imageid";
		$authHeader = array (1 => $this->getAuthHeader());

		try {
			Picasa::do_request($host, $path, $data, "POST", $authHeader, "application/atom+xml");
		} catch (Picasa_Exception $e) {
		   	throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		try {
		    	$retImage = $this->getImageById($username,$albumid,$imageid);
		} catch (Picasa_Exception $e) {
		    	throw new Picasa_Exception("The comment was successfully uploaded but then the following error was encountered: ".$e->getMessage(), $e->getResponse(), $e->getUrl());
		}
		return $retImage;
	}


	/**
	 * Posts a comment to a photo on Picasa.
	 *
	 * @access public
	 * @parameter string $username        The username of the account that the image to comment on is in.
	 * @parameter string $albumid         The album id of the album that the image to comment on is in.
	 * @parameter string $imageid         The image id of the image that the comment is for.
	 * @parameter string $comment         The text of the comment.
	 * @return Picasa_Comment             The comment that was posted.
	 * @throws {@link Picasa_Exception}   If there was a problem sending the comment.  For instance, if the
	 *                                    object is not authenticated or the image does not exist.
	 */
	public function postComment ($username, $albumid, $imageid, $comment) {
		$data = "<entry xmlns='http://www.w3.org/2005/Atom'>
	<content>".$comment."</content>
	<category scheme=\"http://schemas.google.com/g/2005#kind\" term=\"http://schemas.google.com/photos/2007#comment\"/>
</entry>";
		$path="/data/feed/api/user/".$username."/albumid/".$albumid."/photoid/".$imageid;
		$host=Picasa::$PICASA_URL;
		$authHeader = array (1 => $this->getAuthHeader());

		try {
			$imgBuffer = Picasa::do_request($host, $path, $data, "POST", $authHeader, "application/atom+xml");
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		//Picasa will return the image XML.  Parse the id field out and create an image out of it.
		$startBracketPos = strpos($imgBuffer, "<id>") + 4;
		$endBracketPos = strpos($imgBuffer, "</id>");
		$url = substr($imgBuffer, $startBracketPos, $endBracketPos-$startBracketPos);

		try {
			$uploadedComment = new Picasa_Comment(null, $url, $this->contextArray);
		} catch (Picasa_Exception $e) {
		    	throw new Picasa_Exception("The comment was successfully posted, but then the following error was encountered: ".$e->getMessage(), $e->getResponse(), $e->getUrl());
		}
		return $uploadedComment;

	}


	/**
	 * Update the meta data associated with the specified album.
	 * The fields identified by each parameter can be modified, however
	 * which photos appear in an album are not.  Any parameters left null will remain their current value.
	 *
	 * @access public
	 * @param string $username  The username on the account that the album is in.
	 * @param string $albumid   The id number of the album to update.
	 * @param string $title     The title to assign to this album.  Optional, the default is null.
	 * @param string $summary   A summary of the contents of the album.  Optional, the default is an empty string.
	 *                          Optional, the default is null.
	 * @param string $icon      The image that appears as the album cover.  Optional, the default is null.
	 * @param string $rights    The access rights to assign to the album.  Options are "public" and "private".  Optional,
	 *                          the default is "public".  Optional, the default is null.
	 * @param string $commentingEnabled  Identifies whether or not other users should be allowed to post comments to images
	 *                                   in the new album.  Optional, the default is "true".  Optional, the default is null.
	 * @param string $location  The location that the images in the album were taken.  Optional, the default is an empty string.
	 * @param string $timestamp The number of miliseconds after the Unix epoch (January 1, 1970) that the photos in the album were
	 *                          taken.  Notice that the PHP time() functions returns the number of seconds since the epoch, so
	 *                          that number has to be multiplied by 1000 to be used for this parameter.  Optional, the default is
	 *                          null.
	 * @param string $gmlPosition         The location in the world that this image was taken.  The format is latitude and longitude,
	 *                                    separated by a space.  Optional, the default is null.
	 * @return Picasa_Album     The album that was updated.
	 * @throws {@link Picasa_Exception}  If something was wrong with the post to Picasa.  A specific subclass of
	 *                                   {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                   encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                   itself will be thrown.
	 */
	public function updateAlbum ($username, $albumid, $title=null, $summary=null, $icon=null, $rights=null, $commentingEnabled=null, $location=null, $timestamp=null, $gmlPosition=null) {
		$album = $this->getAlbumByIdAsEntry($username, $albumid);

		/**
		 * It's possible that I can just leave all of these parameters out of the XML, but Picasa's documentation
		 * states that you cannot update just part of an album. Preliminary tests have proved that at least I don't know
		 * what that means.  Anyway, this would be reimplimented and tested if I had more time.
		 */
		if ($title == null) {
		    	$title = $album->getTitle();
		}
		if ($summary == null) {
		    	$summary = $album->getSummary();
		}
		if ($rights == null) {
			$rights = $album->getRights();
		}
		if ($commentingEnabled == null) {
		    	$commentingEnabled = $album->getCommentingEnabled();
		}
		if ($location == null) {
		    	$location = $album->getLocation();
		}
		$data = Picasa::constructAlbumXML($title,$summary, $icon, $rights, $commentingEnabled, $location, $timestamp, $gmlPosition, $album->getIdnum());
		$path = $album->getEditLink();
		$path = substr($path, strlen("http://".Picasa::$PICASA_URL));
		$host = Picasa::$PICASA_URL;
		$header = array(1 => $this->getAuthHeader());

		try {
			Picasa::do_request($host,$path,$data, "PUT", $header, "application/atom+xml");
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		try {
		    	$retObj = $this->getAlbumById($username, $albumid);
		} catch (Picasa_Exception $e) {
		    	throw new Picasa_Exception("The album was successfully updated but then the following error was encountered: ".$e->getMessage(), $e->getResponse(), $e->getUrl());
		}
		return $retObj;

	}



	/**
	 * Updates an image in a Picasa Web Album.  This method can be used for updating just the meta data or the meta data and image itself.
	 * Passing null to any of the meta data parameters will cause that value in the album to not update.
	 * To just update the meta data and not the image itself, pass null to $newImageLocation and $type, and make sure at least
	 * one of the meta data parameters is not null.  To update just the image contents and not the meta data, make sure all
	 * meta data parameters are null and the image location is not.
	 *
	 * @access public
	 * @param string $username            The username on the account that the image is in.
	 * @param string $albumid             The id number of the album to that the image is in.
	 * @param string $imageid             The id number of the image to update.
	 * @param string $title               The title that the image will be given.  Optional, the default is null.
	 * @param string $summary             A summary of what is in the image.  Optional, the default is null.
	 * @param string $keywords            A comma-delimited list of keywords associated with the image.  Optional, the default is null.
	 * @param string $commentingEnabled   Set to true if other users should be able to comment on the image and false
	 *                                    if they shouldn't.  Optional, the default is null.
	 * @param string $timestamp           The number of miliseconds after the Unix epoch (January 1st, 1970) that the image was taken (roughly).
	 * @param string $gmlPosition         The location in the world that this image was taken.  The format is latitude and longitude,
	 *                                    separated by a space.  Optional, the default is null.
	 * @param string $newImageLocation    The path to the image on the local file system or network.
	 *                                    You can specify a URL.  This parameter can be null, in which
	 *                                    case only the meta data will be updated.
	 * @param string $type                The type of image that is being uploaded.  Picasa currently accepts "image/bmp",
	 *                                    "image/gif", "image/jpeg", and "image/png".  This parameter can be null if the
	 *                                    $locationOnDisk parameter is also null, in which case on the meta data of the image
	 *                                    will be updated and the image itself will stay the same.
	 * @return Picasa_Image               The image that was updated.
	 * @throws {@link Picasa_Exception}   If something was wrong with the post to Picasa.  A specific subclass of
	 *                                    {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                    encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                    itself will be thrown.
	 * @link http://code.google.com/support/bin/answer.py?answer=63316&topic=10973
	 */
	public function updateImage($username, $albumid, $imageid, $title=null, $summary=null, $keywords=null, $commentingEnabled=null, $timestamp=null, $gmlPosition=null, $newImageLocation=null, $type=null) {
		$host = Picasa::$PICASA_URL;
		$uploadImage = $this->getImageByIdAsEntry($username,$albumid,$imageid);
		$data ="";
		$binaryUpdate = false;
		$metaUpdate = false;

		// Check to see if this will be a multipart document
		if($title!=null || $summary!=null || $keywords!=null || $commentingEnabled!=null || $timestamp!=null || $gmlPosition!=null) {
		    	$metaUpdate = true;
		}
		if ($newImageLocation != null) {
		    	$binaryUpdate = true;
		}

		// Get the XML for the meta data
		if ($metaUpdate) {
			if ($title == null) {
				$title = $uploadImage->getTitle();
			}
			if ($summary == null) {
				$summary = $uploadImage->getDescription();
			}
			if ($commentingEnabled == null) {
				$commentingEnabled = $uploadImage->getCommentingEnabled();
			}
			if ($timestamp == null) {
			    	$timestamp = $uploadImage->getTimestamp();
			}
			$metaXML = Picasa::constructImageXML($title,$summary,$keywords,$commentingEnabled,$timestamp,$gmlPosition);
		}

		// Get the binary data
		if ($binaryUpdate) {
		    	if ($type == null) {
			    	throw new Picasa_Exception("Image must be accompanied by type.");
			}

			$fileContents = @file_get_contents($newImageLocation);
			if ($fileContents === false) {
			    	throw new Picasa_Exception_FileNotFoundException("The specified file could not be found.");
			}
		}

		if ($metaUpdate && $binaryUpdate) {
			$size = strlen($fileContents);
			$path='/data/media/api/user/';
		    	$contentType = "multipart/related; boundary=\"END_OF_PART\"";
			$authHeader = array (1 => $this->getAuthHeader(),
		    	 	             2 => "MIME-version: 1.0\r\n");
			$data = "\r\n
Media multipart posting
--END_OF_PART
Content-Type: application/atom+xml

$metaXML
--END_OF_PART
Content-Type: $type

$fileContents
--END_OF_PART--";
		} else if ($metaUpdate) {
		    	$contentType = "application/atom+xml";
			$authHeader = array (1 => $this->getAuthHeader());
			$path='/data/entry/api/user/';
			$size = strlen($metaXML);
			$data = $metaXML;
		} else if ($binaryUpdate) {
			$size = strlen($fileContents);
			$path='/data/media/api/user/';
		    	$contentType = "$type";
			$authHeader = array (1 => $this->getAuthHeader(),
		    	 	             2 => "MIME-version: 1.0\r\n");
		    	$data = $fileContents;
		} else {
		    	// If nothing was sent for updating, just return the image
			return $uploadImage;
		}

		$path .= $username.'/albumid/'.$albumid.'/photoid/'.$imageid.'/'.$uploadImage->getVersion();
		try {
			Picasa::do_request($host, $path, $data, "PUT", $authHeader, $contentType);
		} catch (Picasa_Exception $e) {
		   	throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		try {
		    $retObj = $this->getImageById($username, $albumid, $imageid);
		} catch (Picasa_Exception $e) {
		    	throw new Picasa_Exception("The image was successfully updated but then the following error was encountered: ".$e->getMessage(), $e->getResponse(), $e->getUrl());
		}
		return $retObj;

	}


	/**
	 * Delete an entire album from Picasa Web Albums.
	 * All images and information associated with the album
	 * will be deleted and is not recoverable (so be careful!).
	 *
	 * @access public
	 * @param string $username  The username on the account that the album is in.
	 * @param string $albumid   The id number of the album to delete.
	 * @return boolean          True if the album was successfully deleted from Picasa.
	 * @throws {@link Picasa_Exception}  If something was wrong with the post to Picasa.  A specific subclass of
	 *                                   {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                   encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                   itself will be thrown.  In the case of an exception, the album is not deleted.
	 */
	public function deleteAlbum ($username, $albumid) {
		$album = $this->getAlbumByIdAsEntry($username, $albumid);
		$path = $album->getEditLink();
		$path = substr($path, strlen("http://".Picasa::$PICASA_URL));
		$host = Picasa::$PICASA_URL;
		$header = array(1 => $this->getAuthHeader());

		try {
			Picasa::do_request($host, $path, null, "DELETE", $header, "application/atom+xml");
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		return true;
	}

	/**
	 * Deletes an image from a Picasa Web Album.
	 * The image and information associated with it will be deleted and
	 * is not recoverable (so be careful!).
	 *
	 * @access public
	 * @param string $username  The username on the account that the image is in.
	 * @param string $albumid   The id number of the album that the image to delete is in.
	 * @param string $imageid   The id number of the image to delete.
	 * @return boolean          True if the image was successfully deleted from Picasa.
	 * @throws {@link Picasa_Exception}  If something was wrong with the post to Picasa.  A specific subclass of
	 *                                   {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                   encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                   itself will be thrown.  In the case of an exception, the album is not deleted.
	 */
	public function deleteImage ($username, $albumid, $imageid) {
		$host = Picasa::$PICASA_URL;
		$deleteImage = $this->getImageByIdAsEntry($username,$albumid,$imageid);

		$host = Picasa::$PICASA_URL;
		$data = "";
		$path= '/data/entry/api/user/'.$username.'/albumid/'.$albumid.'/photoid/'.$imageid.'/'.$deleteImage->getVersion();
		$header = array( 1 => $this->getAuthHeader());

		try {
			Picasa::do_request ($host, $path, $data, "DELETE", $header);
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		return true;
	}

	/**
	 * Deletes a tag applied to a specific image.
	 * Beware that Picasa does not throw an error if the client attempts
	 * to delete a tag that does not exist, so the client will have to do error checking on its own.  If an album or
	 * image that does not exist is supplied, an error will be thrown.
	 *
	 * @access public
	 * @param string $username           The username on the account that the tag is in.
	 * @param string $albumid            The id number of the album that the tag to delete is in.
	 * @param string $imageid            The id number of the image that the tag is on.
	 * @param string $tag                The title of the tag to delete.
	 * @return boolean                   true if the tag was successfully deleted.
	 * @throws {@link Picasa_Exception}  If something was wrong with the post to Picasa. This includes if an invalid
	 *                                   album or image was specified, but not if an invalid tag name was specified.
	 */
	public function deleteTag ($username, $albumid, $imageid, $tag) {
	        $host=Picasa::$PICASA_URL;
		$path='/data/entry/api/user/'.$username.'/albumid/'.$albumid.'/photoid/'.$imageid.'/tag/'.$tag;
		$specialHeaders = array( 1 => $this->getAuthHeader());

		try {
	    		Picasa::do_request($host,$path,null,"DELETE",$specialHeaders);
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
		return true;
	}


	/**
	 * Deletes a comment applied to a specific image in Picasa Web Albums.
	 *
	 * @access public
	 * @param string $username           The username on the account that the comment is in.
	 * @param string $albumid            The id number of the album that the comment to delete is in.
	 * @param string $imageid            The id number of the image that the comment is on.
	 * @param string $tag                The id number of the comment to delete.
	 * @return boolean                   true if the comment was successfully deleted.
	 * @throws {@link Picasa_Exception}  If something was wrong with the post to Picasa.  A specific subclass of
	 *                                   {@link Picasa_Exception} will be thrown based on what kind of problem was
	 *                                   encountered, unless the type of error was unknown, in which case {@link Picasa_Exception}
	 *                                   itself will be thrown.  In the case of an exception, the album is not deleted.
	 */
	public function deleteComment ($username, $albumid, $imageid, $commentid) {
	        $host=Picasa::$PICASA_URL;
		$path='/data/entry/api/user/'.$username.'/albumid/'.$albumid.'/photoid/'.$imageid.'/commentid/'.$commentid;
		$specialHeaders = array( 1 => $this->getAuthHeader());

		try {
	    		Picasa::do_request($host,$path,null,"DELETE",$specialHeaders);
			return true;
		} catch (Picasa_Exception $e) {
			throw Picasa::getExceptionFromInvalidPost($e->getResponse(), $e->getMessage());
		}
	}

	/**
	 * Copies the album attributes along with all images in the album to a new username.
	 * This requires that the current object be authorized to post images to the
	 * destination username's account.  Tags are copied along with each image, however comments are not.
	 * It's the caller's responsibility to make sure the album passed in is what they want copied; keep in mind
	 * that some Picasa functions that fetch albums do not always return all the fields of an image so the parts
	 * that are left out are fetched when their getter is called.  For instance an album returned by
	 * Picasa.getAlbumsByUsername() will not contain the images in it until getImages() is called on it.
	 *
	 * @access public
	 * @param string $destinationUsername	The username of the account that the album will be copied to.
	 * @param {@link Picasa_Album)		The album to copy.  This is the album object and not just the album id.
	 * @returns {@link Picasa_Album}	The new album.
	 * @throws {@link Picasa_Exception}	If the user is not authorized to create albums or upload images to the
	 * 					destination account.
	 */
	public function copyAlbum($destinationUsername, Picasa_Album $album) {
		try {
			$newAlbum = $this->postAlbum($destinationUsername, htmlentities($album->getTitle()), htmlentities($album->getSummary()), $album->getRights(), $album->getCommentingEnabled(), htmlentities($album->getLocation()), $album->getTimestamp(), $album->getIcon(), $album->getGmlPosition());
			$images = $album->getImages();
			foreach ($images as $image) {
				$newImage = $this->copyImage($destinationUsername, $newAlbum->getIdnum(), $image);
			}
		} catch (Picasa_Exception $pe) {
			throw $pe;
		}
		return $newAlbum;
	}

	/**
	 * Copies an image with most attributes from one album to the other.
	 * This requires that the current object be authorized to post images to the destination
	 * username's account.  Most attributes, including
	 * tags, are copied, although comments are not.
	 *
	 * @access public
	 * @param string $destinationUsername	The username for the account the image will be copied to.
	 * @param string $destinationAlbumId	The id of the album the image will be copied to.
	 * @param {@link Picasa_Image} $image	The image to copy.  This is the image object and not just the image id.
	 * @returns {@link Picasa_Image}	The newly copied image.
	 * @throws {@link Picasa_Exception}	If the user is not authorized to create albums or upload images to the destination account.
	 */
	public function copyImage($destinationUsername, $destinationAlbumId, Picasa_Image $image) {
		try {
			$tags = $image->getTags();
			$keywords = "";
			foreach($tags as $tag) {
				$keywords .= $tag.",";
			}
			$newImage = $this->postImage($destinationUsername, $destinationAlbumId, $image->getContent(), $image->getImageType(), htmlentities($image->getTitle()), htmlentities($image->getDescription()), htmlentities($keywords), $image->getCommentingEnabled(), $image->getTimestamp(), $image->getGmlPosition());
		} catch (Picasa_Exception $pe) {
			throw $pe;
		}
		return $newImage;
	}


	/**
	 * Fetches the authorization token from the user's cookies- if one exists- and logs the user in using the retrieved token.
	 * It automatically determines which type of authorization (AuthSub or Client Login) to use based on the type of cookie
	 * that is retrieved.  If both types exist in the users' cookies, the default is AuthSub.
	 *
	 * @access public
	 * @return boolean     true if setting the authorization information in the cookie was successful, false otherwise.
	 */
	 public function authorizeFromCookie() {
		if (array_key_exists(Picasa::$COOKIE_NAME_AUTH_SUB_TOKEN, $_COOKIE) && strcmp($_COOKIE[Picasa::$COOKIE_NAME_AUTH_SUB_TOKEN],"") !== 0) {
			$this->setAuthorizationInfo($_COOKIE[Picasa::$COOKIE_NAME_AUTH_SUB_TOKEN], Picasa::$AUTH_TYPE_AUTH_SUB);
			return true;
		} else if (array_key_exists(Picasa::$COOKIE_NAME_CLIENT_LOGIN_TOKEN, $_COOKIE) && strcmp($_COOKIE[Picasa::$COOKIE_NAME_CLIENT_LOGIN_TOKEN],"") !== 0) {
			$this->setAuthorizationInfo($_COOKIE[Picasa::$COOKIE_NAME_CLIENT_LOGIN_TOKEN], Picasa::$AUTH_TYPE_CLIENT_LOGIN);
			return true;
		} else {
		    	return false;
		}
	 }


	/**
	 * Builds a context array from the authorization information in the current instantiation.
	 * This context array is used in several
	 * places, including the constructors for {@link Picasa_Image} and {@link Picasa_Album}.  The context array is built in such a way
	 * that it can be passed to PHP {@see stream_context_create()} to be used in GET requests.
	 *
	 * @access protected
	 * @param boolean $doAuth       true if the authorization information should be included in the array, false otherwise.  Sometimes
	 *                              it may be desireable to do an unauthorized request for information from Picasa even when the
	 *                              client has established authorization.  Optional, the default is false.
	 * @param string $method        The request type that will be used.  This context array in practice is typically only used directly
	 *                              for the method {@see file_get_contents()}, which is a get request, so it is probably always correct
	 *                              to pass "GET" here, which is the default value.
	 * @param int $contentLength    The size of the data in the request.  For "GET" requests, this can be 0, which is the default.
	 * @param string $contentType   The type of content being sent in the request.  Optional, the default is application/x-www-form-urlencoded.
	 * @return array                A context array that can be stored in {@link Picasa::$contextArray} and passed to the constructors for
	 *                              other classes in the API such as {@link Picasa_Image} and {@link Picasa_Album}.
	 * @link http://www.php.net/stream_context_create
	 * @see Picasa::contextArray
	 */
	protected function constructContextArray($doAuth=false, $method="GET", $contentLength=0, $contentType="application/x-www-form-urlencoded") {
	    	$header = "";

		// If the auth header line should be included
		if ($doAuth) {
		    //$header = array ( 1 => $this->getAuthHeader());
		    $header = $this->getAuthHeader();
		}
		$header .= "Content-Type: ".$contentType."\r\nContent-Length: ".$contentLength."\r\nConnection: Close\r\n\r\n";

		$opts = array(
			'http' => array (
			    	'method' => $method,
				'header' => $header
			    	)
			);
		return $opts;
	}


	/**
	 * Executes a GET request on the $url passed in and traps the error code to pass with the exception.
	 * This is necessary because queries in this API are executed using {@link file_get_contents} typically, which does not supply the response
	 * code in the case of a response greater than 201 (it considers the file nonexistant in such a case and just returns false).
	 * With this message, the client can get a useful error message that actually comes from Picasa.
	 *
	 * Note that this does not actually throw an exception, it just determines what kind of exception could be thrown
	 * based on the response.  It's up to the caller to actually throw the exception.
	 *
	 * @access public
	 * @param string $url           The URL to request, including the host and path.
	 * @param array $contextArray   The context array that was passed with the original request.  This is so that a different
	 *                              request is not executed when this method is called, thus resulting in a different error message.
	 * @return Picasa_Exception     If the response was empty.
	 *
	 */
	public static function getExceptionFromInvalidQuery ($url, $contextArray) {
		Picasa_Logger::getLogger()->logIfEnabled("URL that caused the error: ".$url);
		$host=Picasa::$PICASA_URL;
		$startHost = strpos($url, $host);
	    	if ($startHost === false) {
			return new Picasa_Exception_BadRequestException("A malformed URL was provided.", null, $url);
		}
		//$auth = urlencode($auth);
		$path=substr($url, $startHost+strlen($host));
		if ($contextArray == null) {
			$header="Content-Type: application/x-www-form-urlencoded\r\nContent-Length: 0\r\nConnection: Close\r\n\r\n";
		} else {
			$header = $contextArray['http']['header'];
		}
		$fp = fsockopen($host, 80, $errno, $errstr);
		if (!$fp) {
			return new Picasa_Exception($errstr);
		}
		fputs($fp, "GET $path HTTP/1.1\r\n");
		fputs($fp, "Host: $host\r\n");
		fputs($fp, $header);

		$break = false;
		$buf = "";
		$totalbuf = "";
		while (!$break && !feof($fp)) {
			$buf = @fgets($fp);
			$totalbuf .= $buf;
			if (strcmp($buf, "Connection: close\r\n") == 0) {
				$totalbuf .= @fgets($fp);
				$totalbuf .= @fgets($fp);
				$totalbuf .= @fgets($fp);
				$buf = @fgets($fp);
				$totalbuf .= $buf;
				$break = true;
			} else {
				$buf="";
			}
		}
		fclose($fp);

		Picasa_Logger::getLogger()->logIfEnabled("Total buffer response from Picasa: ".$totalbuf);

		if (strcmp($buf, "") == 0) {
			return new Picasa_Exception("An unknown error has occured.", null, $url);
		} else {
			return new Picasa_Exception_BadRequestException($buf, null, $url);
		}
	}

	/**
	 * Matches the supplied $buf contents against several different HTTP response codes to determine what kind of Picasa_Exception to return.
	 * Note that this does not actually throw an exception, it just determines what
	 * kind of exception could be thrown based on the response.  It's up to the caller to actually throw the exception.
	 *
	 * The name of this method is a little misleading; the response does not need to be from a POST, it can be a response
	 * from any kind of request.
	 *
	 * @param string $buf      The HTTP response to check for the response code in.
	 * @param string $message  The message to set in the exception.  Optional, the default is null.  If null is supplied,
	 *                         a default message will be set based on what type of response code is sent.
	 * @return Picasa_Exception A type of Picasa_Exception depending on what response code was found.  If the response
	 *                                  code is not recognized, then a Picasa_Exception itself is thrown.
	 */
	public static function getExceptionFromInvalidPost($buf, $message=null) {
		Picasa_Logger::getLogger()->logIfEnabled("Buffer with exception: ".$buf);
		if (preg_match("/401 UNAUTHORIZED/i", $buf)){
			if ($message == null) {
			    	$message = "Authorization no longer valid.";
			}
			return new Picasa_Exception_UnauthorizedException($message, $buf);
		} else if (preg_match("/403 FORBIDDEN/i", $buf)){
			if ($message == null) {
			    	$message = "Request forbidden.";
			}
			return new Picasa_Exception_UnauthorizedException($message, $buf);
		} else if (preg_match("/400 BAD REQUEST/i", $buf)){
			if ($message == null) {
			    	$message = "The request was invalid.";
			}
			return new Picasa_Exception_BadRequestException($message, $buf);
		} else if (preg_match("/500 INTERNAL/i", $buf)){
			if ($message == null) {
			    	$message = "An error occured on the Picasa servers.";
			}
			return new Picasa_Exception_InternalServerErrorException($message, $buf);
		} else if (preg_match("/409 CONFLICT/i", $buf)){
			if ($message == null) {
			    	$message = "An error occured on the Picasa servers.";
			}
			return new Picasa_Exception_ConflictException($message, $buf);
		} else {
			if ($message == null) {
			    	$message = "An unknown error was encountered.";
			}
	    		return new Picasa_Exception($message, $buf);
		}
	}

	/**
	 * Constructs the line that is necessary for passing authorization information within the HTTP header to Picasa.
	 * The object must already be authenticated when this method is called.  Will work with
	 * either AuthSub or Client Login authorizations.  Note that the line has "\r\n" already appended at the end
	 * because it is required for HTTP headers.
	 *
	 * @access public
	 * @return string    The header.
	 */
	public function getAuthHeader () {
		// The name that identifies the token changes depending on if it's a Client Login auth or AuthSub
		$tokenName = "token";
		$authTypeName = "AuthSub";
		if ($this->authType == Picasa::$AUTH_TYPE_CLIENT_LOGIN) {
			$tokenName = "auth";
			$authTypeName = "GoogleLogin";
		}
		return "Authorization: ".$authTypeName." ".$tokenName."=".$this->auth."\r\n";
	}


	/**
	 * Generates the XML necessary for creating a Picasa Web Album.
	 *
	 * @access protected
	 * @static
	 * @param string $title     The title to assign to the created album.
	 * @param string $summary   A summary to assign to the created album.  Optional, the default is an empty string.
	 * @param string $icon      The image that appears as the album cover.
	 * @param string $rights    The rights to assign to the created album.  This value must be "private" or "public".
	 *                          Optional, the default is "public".
	 * @param boolean $commentingEnabled  true allows logged in users to post comments in this album.  false
	 *                                    disallows all commenting.  Optional, the default is true.
	 * @param string $location  The location that the photos in the album were taken.  Optional, the default is an empty string.
	 * @param string $timestamp The number of miliseconds after the Unix epoch (January 1, 1970) that the photos in the album were
	 *                          taken.  Notice that the PHP time() functions returns the number of seconds since the epoch, so
	 *                          that number has to be multiplied by 1000 to be used for this parameter.  Optional, the default is
	 *                          null.  Passing null here will set the timestamp to the current time.
	 * @param string $gmlPosition         The location in the world that this image was taken.  The format is latitude and longitude,
	 *                                    separated by a space.  Optional, the default is null.
	 * @param string $albumid   The id number of the album.  This is applicable if the XML is being built to update an existing album.
	 * @return string           The XML for uploading the described album.
	 */
	protected static function constructAlbumXML($title, $summary="", $icon=null, $rights="public", $commentingEnabled="true", $location="", $timestamp=null, $gmlPosition=null, $albumid=null) {
		$xml = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:media='http://search.yahoo.com/mrss/' xmlns:gphoto='http://schemas.google.com/photos/2007' xmlns:georss='http://www.georss.org/georss' xmlns:gml='http://www.opengis.net/gml'>
	<title type='text'>$title</title>
	<summary type='text'>$summary</summary>";
		if ($icon != null) {
		    	$xml.="
	<icon>$icon</icon>";
		}
		if ($albumid != null) {
		    	$xml.="
	<gphoto:id>$albumid</gphoto:id>";
		}
		if ($timestamp != null) {
		    	$xml.="
	<gphoto:timestamp>$timestamp</gphoto:timestamp>";
		}
		if ($gmlPosition !== null) {
		    	$xml .="
	<georss:where>
		<gml:Point>
			<gml:pos>$gmlPosition</gml:pos>
		</gml:Point>
	</georss:where>";
		}
		$xml.="
	<gphoto:location>$location</gphoto:location>
	<gphoto:access>$rights</gphoto:access>
	<gphoto:commentingEnabled>$commentingEnabled</gphoto:commentingEnabled>
	<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/photos/2007#album'></category>
</entry>";
		return $xml;
	}



	/**
	 * Generates the XML necessary for creating a Picasa image.
	 *
	 * @access protected
	 * @static
	 * @param string $title               The title that the image will be given.
	 * @param string $summary             A summary of what is in the image.  Optional, the default is an empty string.
	 * @param string $keywords            A comma-delimited list of keywords associated with the image.  Optional, the default is null.
	 * @param string $commentingEnabled   Set to true if other users should be able to comment on the image and false
	 *                                    if they shouldn't.  Optional, the default is true.
	 * @param string $timestamp           The number of miliseconds after the Unix epoch (January 1st, 1970) that the image was taken (roughly).
	 * @param string $gmlPosition         The location in the world that this image was taken.  The format is latitude and longitude,
	 *                                    separated by a space.  Optional, the default is null.
	 * @return string                     XML that can be sent to Picasa to create an image.
	 */
	protected static function constructImageXML($title,$summary="",$keywords=null,$commentingEnabled="true", $timestamp="", $gmlPosition=null) {
		$retString = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gphoto='http://schemas.google.com/photos/2007' xmlns:georss='http://www.georss.org/georss' xmlns:gml='http://www.opengis.net/gml' xmlns:media='http://search.yahoo.com/mrss/'>
	<title>$title</title>
	<summary>$summary</summary>
	<gphoto:commentingEnabled>$commentingEnabled</gphoto:commentingEnabled>
	<gphoto:timestamp>$timestamp</gphoto:timestamp>";
		if ($gmlPosition !== null) {
		    	$retString .="
	<georss:where>
		<gml:Point>
			<gml:pos>$gmlPosition</gml:pos>
		</gml:Point>
	</georss:where>";
		}
		if ($keywords !== null) {
		    	$retString .="
	<media:group>
	    	<media:keywords>$keywords</media:keywords>
	</media:group>";
		}
		$retString.="
	<category scheme=\"http://schemas.google.com/g/2005#kind\" term=\"http://schemas.google.com/photos/2007#photo\"/>
</entry>";
		return $retString;
	}



	/**
	 * Constructs a string of optional query parameters for requesting a feed from Picasa.
	 * All parameters are optional, their defaults are all null.  Passing a null value for a parameter will exclude the
	 * value from the parameter list.
	 *
	 * @access protected
	 * @static
	 * @param int $maxResults  The maximum number of results to return.
	 * @param int $startIndex  The first element number with the search results to return.  Useful for pagination.
	 * @param string $keywords Space-delimited list of keywords to search for.
	 * @param string $tags     Space-delimited list of tags to search for.
	 * @param string $visibility Restrict the search to comments in images with the access rights specified here.
	 * @param int $thumbsize   Comma-delimited list of thumbnail sizes to fetch.
	 * @param int $imgmax      Size of image to return in the $content field of the {@link Picasa_Image} field.
	 * @param string $location The location that the image was taken.
	 * @param string $sortDescending Whether or not to sort the items returned by date added descending.
	 * @param string $boundingBox	Geo coordinates to search for photos within in the order west, south, east, north.
	 * @param string $location	Named location to search for photos in.  For example, London.
	 * @return string          A string with optional query parameters strung together with ampersands.
	 */
	protected static function buildQueryParams($maxResults=null, $startIndex=null, $keywords=null, $tags=null, $visibility=null, $thumbsize=null, $imgmax=null, $location=null, $sortDescending=null, $boundingBox=null, $location=null) {
		$query = '';

		if ($maxResults !== null) {
		    	$query .= '&max-results='.$maxResults;
		}

		if ($startIndex !== null) {
		    	$query .= '&start-index='.$startIndex;
		}

		if ($visibility !== null) {
		    	$query .= '&access='.$visibility;
		}

		if ($keywords !== null) {
			$query .= '&q='.$keywords;
		}

		if ($tags !== null) {
		    	$query .= '&tag='.$tags;
		}

		if ($thumbsize !== null) {
		    	$query .= '&thumbsize='.$thumbsize;
		}

		if ($imgmax !== null) {
		    	$query .= '&imgmax='.$imgmax;
		}
		if ($location !== null) {
		    	$query .= '&l='.$location;
		}
		if ($sortDescending === true && $keywords === null) {
			$query .= '&q=';
		}
		if ($boundingBox !== null) {
			$query .= '&bbox=';
		}
		if ($location !== null) {
			$query .= '&l=';
		}
		Picasa_Logger::getLogger()->logIfEnabled("Query string built: ".$query);
		return $query;
	}


	/**
	 * Executes a request.
	 * This is a pretty generic PHP function for the most part.  The only parts that are specific to Picasa
	 * is the errors that are thrown.  PHP doesn't have a built in function that does this very well (although there are packages
	 * for it that can be installed), so this manually sets the HTTP headers and parses the whole response.
	 *
	 * @access protected
	 * @static
	 * @param string $host       The destination address to send the request to.  It should not include a protocol.
	 * @param string $path       The path on the host to send the request to.  It should start with a "/"
	 * @param string $data       Any data that should be sent along with the request.  If there is no data, leave it as null.
	 * @param string $request    The type of request to perform.  Most common are GET, POST, PUT, and DELETE.  The type of
	 *                           request to use for each Picasa function is defined in Picasa's official documentation.
	 * @param array $specialHeaders  An array of strings of headers that should be sent with the request.  The headers that are
	 *                               always sent are Host, Content-Type, and Content-Length.  The most common type of special
	 *                               header is an authorization header from Google.  Note that even if there is only one special header,
	 *                               it still must be in an array.  Also note that each line in the array should end in "\r\n" and
	 *                               should be set in the array with double quotes, not single quotes, because "\r\n" is interpreted
	 *                               differently by PHP if single quotes are used.  Optional, the default is null.
	 * @param string $type       The type of content that will is being sent through the request.  Optional, the default is
	 *                           "application/x-www-form-urlencoded".
	 * @param string $protocol   The protocol to use when sending the request.  Secure requests should use "ssl://".  Note that
	 *                           the protocol must end in "://" unless it's an empty string.  For HTTP, this parameter can be left
	 *                           as an empty string.  Optional, the default is an empty string.
	 * @param int $port          The port number to send the request to.  Different protocols have different port numbers.  HTTP protocol
	 *                           uses port 80 and SSL uses port 443.  Optional, the default is 80.
	 * @return string            The entire response, including headers, that was recieved from the host.
	 * @throws {@link Picasa_Exception}  If a response of "200" or "201" is not recieved.  In this case, the entire contents of the response,
	 *                                   including headers, is set to the $response field of the exception and the error supplied by
	 *                                   Picasa is set as the exceptions message.  The idea is that the calling method can search the
	 *                                   response for a specific return code (for instance, a File Not Found or Forbidden error) and throw
	 *                                   a more specific exception.  The caller can also search the response for values that are specific
	 *                                   to its request, such as a Captcha URL.
	 */
	protected static function do_request ($host, $path, $data, $request, $specialHeaders=null, $type="application/x-www-form-urlencoded", $protocol="", $port="80")
	{
	    	$contentlen = strlen($data);
		$req = "$request $path HTTP/1.1\r\nHost: $host\r\nContent-Type: $type\r\nContent-Length: $contentlen\r\n";
		if (is_array($specialHeaders))
		{
		    	foreach($specialHeaders as $header)
			{
			    	$req.=$header;
			}
		}
		$req.="Connection: close\r\n\r\n";
		if ($data != null) {
			$req.=$data;
		}
		Picasa_Logger::getLogger()->logIfEnabled("Request to do: ".$request);
		$fp = fsockopen($protocol.$host, $port, $errno, $errstr);
		if (!$fp) {
			throw new Picasa_Exception($errstr);
		}
		fputs($fp, $req);

		$buf = "";
		if (!feof($fp)) {
			$buf = @fgets($fp);
		}
		Picasa_Logger::getLogger()->logIfEnabled("Buffer returned: ".$buf);
		// If either a 200 or 201 response is not found, there was a problem so throw an exception
		if (preg_match("/200 /i",$buf) || preg_match("/201 /i",$buf)) {
			while (!feof($fp)) {
				$buf .= @fgets($fp);
			}
			fclose($fp);
			return $buf;
		} else {
		    	/* In the response returned from Picasa, it is really hard to pull out the error message.
			 * Its location is two lines below the "Connection: Close" line.  So that message is pulled
			 * out, if possible, and set as the Exception's message.  Also, the entire buffer is sent.
			 * This way, the caller can throw it's own message by looking for its own response code.
			 */
		    	$expMessage = "An unknown error has occured while sending a $request request.";
			$break = false;
			$tmpBuf = "";
			while (!feof($fp)) {
				$tmpBuf = @fgets($fp);
				$buf.=$tmpBuf;
				if (strcmp($tmpBuf, "Connection: Close\r\n") == 0) {
					for ($i=0; !feof($fp); $i++) {
						if ($i == 2) {
						    	$expMessage = @fgets($fp);
							$buf .= $expMessage;
							$break = true;
						} else {
							$buf .= @fgets($fp);
						}
					}
				}
			}
			if (!$break) {
				$msg = Picasa::getResponseValue($buf,"Error");
				if ($msg != null) {
					$expMessage = $msg;
				}
			}
			throw new Picasa_Exception($expMessage, $buf, $host.$path);
		}
	}

	/**
	 * Looks in the supplied response for the supplied key and finds the value associated with the key if one exists.
	 * Searches between the supplied key and the first linebreak.  The value searched for cannot have a linebreak in it.
	 *
	 * @static
	 * @access protected
	 * @param string $response   The haystack to look for the key in.
	 * @param string $key        The needle to look for in the haystack.
	 * @return $string           The value associated with the key.  The value will be the string between the
	 *                           key with an equals sign next to it, and the end of the line.  Returns null
	 *                           if no value is found.
	 */
	protected static function getResponseValue($response, $key) {
		$key = $key."=";
		$errStr = null;
		$errPos = strpos($response, $key);
		if ($errPos !== false) {
			$startPos = $errPos + strlen($key);
		    	// Get the position of the first line break after the error
			$endPos = strpos($response, PHP_EOL, $startPos);
			$errStr = substr($response, $startPos, $endPos-$startPos);
		}
		return $errStr;
	}


	/**
	 * Constructs a textual representation of everything in the current instantiation of the object.
	 *
	 * @return string
	 * @access public
	 */
	public function __toString() {
	    	$authType = "";
		if ($this->authType === Picasa::$AUTH_TYPE_CLIENT_LOGIN) {
			$authType = "Client Login";
		} else if ($this->authType === Picasa::$AUTH_TYPE_AUTH_SUB) {
			$authType = "AuthSub";
		}

		$retstring = "
[ TYPE:        Picasa
  EMAILADDRESS:".$this->emailAddress."
  AUTH:        ".$this->auth."
  AUTHTYPE:    ".$authType."
]";
		return $retstring;
	}


}
