Lightweight PHP Picasa API v3.3 Documentation
Copyright 2008 Cameron Hinkle

INTRODUCTION

Thank you for choosing to use this API.  It is distributed
under the GNU General Public License version 3 (GPLv3).  I
hope that it will be useful to you.  I encourage anyone who
uses this software to modify and improve it.  Please contact
me if you make improvements to it or if you need help using it.
You can email me at goldplateddiapers@gmail.com or visit
http://www.cameronhinkle.com/blog for tutorials and help.  You can
also see this API in use at http://www.cameronhinkle.com/pictures.

CHANGES

In version 3.0, the following changes have been made since version 1.0:
* Automatic query building with the Picasa class
* Improved error handling through the Picasa_Exception package
* Support for authentication
* Support for posting and deleting images, albums, comments, and tags

Version 3.0 has been carefully constructed to be completely 
compatible with previous version.  In other words, if you install
version 3.0 after previously using version 2.0 or 1.0, your previous
implementation should still work and you will have greater flexibility
to add new features to your software in the future. 

WHAT IS THIS?

This is essentially a PHP wrapper for Google's Picasa Data API, which
is implemented using Atom feeds in XML.  The package is meant to
make it easy for PHP developers to integrate their own applications
with Picasa.

REQUIREMENTS

This software requires PHP version 5 to be installed.  Version 5.2
is recommended because of the use of __toString() methods, although
if you do not wish to print objects using __toString(), then
PHP 5.0 will work fine.

INSTALLATION

To install this software, simply place it within your include path.
You can set your include path by calling ini_set('include_path', '/PATH/').
Once properly installed within your include path, you should be able
to include the objects within the API in your own php files.

The helper functions located in the Cam directory should not be used and are
officially not part of the API.  They are included for backwards compatibility.
However, to use them, open Cam_Util_PictureUtil and replace the text
YOUR_USERNAME_HERE with the username that you will be using as the default
Picasa account.  The text should be replaced in two places, the $USER and
$BASE_QUERY_URL fields.  Several of the functions within the helper files
rely on a default account.

USE

To properly use the classes in this API, you may need knowledge
of Picasa's API, which is documented at 
http://code.google.com/apis/picasaweb/gdata.html. A lot of work has
been put into making the documentation and code as complete as possible
so that you need to know as little as possible about the Data API,
but it will still be helpful if you are familiar with it.
 
HELP

For help and questions, visit http://www.cameronhinkle.com/ or 
email Cameron Hinkle directly at goldplateddiapers@gmail.com/. 
