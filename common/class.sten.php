<?php

  /* *************************************************************************
   * Sten
   * Written/tested using PHP 5.2
   *
   * File:	class.sten.php
   * Author:	Grant Jackson
   * Package:	Sten -- Simple (HTML) Templating ENgine written in PHP.
   *
   * Copyright (C) 2010
   * Licensed under GPLv3. GNU GENERAL PUBLIC LICENSE, Version 3, 29 June 2007
   *
   * This program is free software: you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation, either version 3 of the License, or
   * (at your option) any later version.
   *
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   *
   * You should have received a copy of the GNU General Public License
   * along with this program.  If not, see <http://www.gnu.org/licenses/>
   *
   * *************************************************************************/
  // Globals
  $nl = "<br />\n";		// HTML newline

  /////////////////////////////////////////////////////////////////////////////
  /**
   * STEN is a "Simple Template ENgine" for separating HTML content from
   * HTML/CSS style and layout.
   */
  class Sten
  {
    // Constants
    const DEFAULT_LAST_MOD_DATE_FORMAT = 'D Y-m-d, H:i:s T(O)';	// Default format string for date()
    const DEFAULT_TIMEZONE = 'Australia/South';			// Default timezone
    const GLOB_EXT = '.inc.html';	// Globs must have this file-extention (suffix)

    // Instance vars
    private $tokens;			// A collection of tokens

    private $last_modified;		// Last modified timestamp
    private $last_modified_date_format; // Last modified date-format string, for date()
    private $timezone;			// Timezone (for display of last modified timestamp)

    private static $debug = false;	// Default: debug is off
    private static $page;		// Web page for displaying debug info

    //
    // Create a new Sten (template) object
    //
    public function __construct()
    {
      $this->last_modified = 0;		// Default: last modified timestamp = the Epoch
      $this->last_modified_date_format = Sten::DEFAULT_LAST_MOD_DATE_FORMAT;
      $this->timezone = Sten::DEFAULT_TIMEZONE;
      $this->tokens = new Stentokens();	// Create a collection of tokens
    }

    //
    // Set the last-modified timestamp and optionally the corresponding format string for date().
    // Provides a means of setting the last-modified timestamp of the calling PHP script.
    // Typical invocation:
    //   $tpl->setLastMod( filemtime("$_SERVER[SCRIPT_FILENAME]") );
    //
    public function setLastMod($last_modified, $last_modified_date_format=Sten::DEFAULT_LAST_MOD_DATE_FORMAT)
    {
      $this->last_modified = $last_modified;
      $this->last_modified_date_format = $last_modified_date_format;
    }

    //
    // Get the last-modified timestamp.
    //
    public function getLastMod() { return $this->last_modified; }

    //
    // Get the max last-modified timestamp (between arg and this obj).
    //
    public function setMaxLastMod($timestamp)
    { $this->last_modified = max($this->last_modified, $timestamp); }

    //
    // Set the debug status.
    // Debug info will only be collected while this property is true.
    // Debug info will only be displayed if show() is called while this property is true.
    //
    public function setDebug($debug=true)
    {
      self::$debug = $debug;

      global $nl;				// Newline
      $msg = "$nl Notes: " .
        "<ul> " .
          "<li>The potential problems listed on this page may not actually cause a problem (eg. if the token is never matched/invoked).</li> " .
          "<li>There might be problems in addition to those listed on this page.</li> " .
        "</ul> " .
        "$nl <h1>Checking tokens within object</h1> ";

      self::$page = new SimplePage();		// html page for displaying debug text
      self::$page->appendMsg($msg);
    }

    //
    // Set the timezone
    //
    public function setTimezone($tz) { $this->timezone = $tz; }

    //
    // Setup the token details
    //
    public function addToken($name, $type, $value)
    {
      $token = new StenToken($name, $type, $value);
      if(self::$debug) $this->checkToken($token);
      $this->tokens->add($token);
    }

    //
    // Show/display template (or debug info)
    //
    public function show($template_file)
    {
      if(self::$debug)
        $this->showProblems($template_file);
      else
        $this->showTpl($template_file);
    }

    //
    // Example invocation:
    //   $tpl = new Sten();			// Create template obj
    //   //$tpl->setDebug();			// Switch on debug/verification web-page
    //   $tpl->setTimezone('Australia/NSW');	// Optionally change default TZ of object
    //   $tpl->setLastMod(filemtime("$_SERVER[SCRIPT_FILENAME]"));	// Supply last mod time of caller
    //
    //   // Populate the Sten-template object with tokens. Argument order is:
    //   //   'tokenName', 'tokenType', "tokenValue"
    //   $tpl->addToken('title',   'text',      "Page title goes here");	// Replace token with this string
    //   $tpl->addToken('linkCss', 'text',      "/FS_PATH/TO/MY.css");	// Replace token with this string
    //   $tpl->addToken('content', 'file_glob', "myprefix");		// Replace token with content in 'myprefix*.inc.html'
    //   $tpl->addToken('footer',  'file_tpl',  "/FS_PATH/TO/FTR.inc.html");	// Replace token with content in template-file
    //   $tpl->addToken('lastMod', 'last_mod',  "");			// Replace token with last modified timestamp
    //   $tpl->addToken(...);
    //   $tpl->show("FILESYS/PATH/TO/TEMPLATE.html");	// Show the template (with tokens replaced with content)
    //
    // Define 'HTML template': An html file where special tokens (or keys)
    // will be replaced by other content as determined by the properties of
    // those tokens.
    //
    // Purpose: Style and layout are determined by a single CSS file partnered
    // with a single HTML template file. However the tokens within the template
    // allow you have individual content on each page on your web-site by
    // having one PHP file (eg. index.php) per web-page which loads the
    // tokens with appropriate content.
    //
    // This function sends the specified 'HTML template' to stdout (ie. the
    // browser), while replacing template-file tokens from the Sten template-object
    // on-the-fly. A maximum of 1 substitution is permitted per template-line.
    //
    // Within the template file, "<!-- {{TOKEN_NAME}} -->" will be replaced.
    // TOKEN_NAME can be any upper or lower case alpha-numeric plus dot '.' and
    // underscore '_'.  In order to be replaced, the TOKEN_NAME in the file must
    // match a TOKEN_NAME in the Sten object.  The type of replacement shall be
    // determined by the TOKEN_TYPE and TOKEN_VALUE associated with the TOKEN_NAME.
    //
    // TOKEN_TYPE must be one of the following strings (or the token name/key will
    // be ignored):
    //
    // - 'text' will be replaced with the token-value text
    //
    // - 'file' will be replaced with the contents of the file specified by the
    //   token-value
    //
    // - 'file_glob' will be replaced with the contents of all files which match
    //   the prefix given by the token-value and match the suffix ".inc.html"
    //   (with zero or more chars between the prefix and suffix). ie. the
    //   contents of the files matching "TOKEN_VALUE*.inc.html"
    //
    // - 'file_tpl' is similar to token-type 'file' except the file specified by
    //   token-value can contain token-names/keys which will also be replaced by
    //   the same set of tokens. The template file referenced by the token-value
    //   may itself contain a token which points to another template.
    //   BEWARE: The web designer MUST ensure the recursion terminates!
    //
    // - 'last_mod' will be replaced with the 'most recent' last-modified time
    //   from the following list:
    //   - $last_modified arg given by the calling program via setLastMod()
    //   - last-modified time of the $template_file
    //   - last-modified time of any files specified by 'file', 'file_tpl'
    //     and 'file_glob' tokens/variables above
    //   The last-modified time of the following will be ignored:
    //   - CSS, Javascript, image, etc files unless the file is specified via one
    //     of the tokens of token-type 'file*'
    //   - the class-file containing this method (ie. the showTpl() method)
    //   If the token-value is an empty string then the Sten object's date() format
    //   string shall be used, else the token value shall be used as the format string.
    //
    //
    private function showTpl($template_file)
    {
      $fh = fopen($template_file, "r");
      if(!$fh)
      {
        $page = new SimplePage();
        $page->show('ERROR: Cannot open file', "File '$template_file' cannot be opened for reading or does not exist");
      }

      date_default_timezone_set($this->timezone);
      $this->setMaxLastMod(filemtime($template_file));
      while(!feof($fh))
      {
        $line = fgets($fh);
        if( preg_match("/^(.*)<!-- *{{([A-Za-z0-9_\.]+)}} *-->(.*)$/", $line, $position) &&
          $this->tokens->keyExists($position[2]) )
        {
          echo $position[1];			// Show line-fragment to the left of substitution

          $key = $position[2];			// The token name (key)
          $tkValue = $this->tokens->get($key)->getValue();	// The token (substitution) value
          switch( $this->tokens->get($key)->getType() )	// Switch based token type
          {
          case 'text':				// Substitute token for text
            echo $tkValue;
            break;

          case 'file':				// Substitute token for file contents
            readfile($tkValue);
            $this->setMaxLastMod(filemtime($tkValue));
            break;

          case 'file_glob':			// Substitute token for contents of all files matching VALUE*GLOB_EXT
            // If you set the *value* of a glob-token to '' (ie. empty string) then it will match ALL
            // files with the glob file-extention! This might not be what you intended.
            foreach(glob("$tkValue*" . Sten::GLOB_EXT) as $fname)	// For each matching file
            {
              readfile($fname);       		// Send file to stdout
              $this->setMaxLastMod(filemtime($fname));
            }
            break;

          case 'file_tpl':			// Allows variable-substitution into the file $tkValue
            // BEWARE: Ensure the recursion terminates!
            $this->setMaxLastMod(filemtime($tkValue));	// Update last mod time
            $new_tpl = clone $this;			// $new_tpl gets a copy of last mod time
            $new_tpl->show($tkValue);			// Show the (sub)template. May update last mod time
            $this->last_modified = $new_tpl->getLastMod();	// Update last mod time of $this
            break;

          case 'file_csv':		
            $this->setMaxLastMod(filemtime( $tkValue->getFname() ));	// Update last mod time
            echo $tkValue->toTableStr();		// Token-value is a CSV obj. Display as an html table
            break;

          case 'last_mod':		// Substitute last-modified string for this token
            // Since last-modified time is calculated on-the-fly, it may display a time
            // which is 'too early' if the token appears too early in the template. It will
            // be accurate if it is a token in the LAST FILE to be parsed.
            if($tkValue == '')		// Use the Sten object's date() format string
              echo date($this->last_modified_date_format, $this->last_modified);
            else			// Use the token value as date() format string
              echo date($tkValue, $this->last_modified);
            break;

          default:
            // Do nothing if there is no match
          }
          echo $position[3];			// Show line-fragment to the right of token name
        }
        else
          echo $line;
      }
      fclose($fh);
    }

    ///////////////////////////////////////////////////////////////////////////
    // Debug functions below here
    ///////////////////////////////////////////////////////////////////////////

    //
    // showProblems
    // A debug routine.
    // Debug info has already been accumulating (eg. as tokens are added)
    //
    private function showProblems($template_file)
    {
      $level = 1;		// html header level ie. <h1>, <h2>, <h3>...

      // To validate:
      // - all tokens within object are used
      // - main template file is readable
      // - all template files contain tokens
      // - recursion

      self::$page->appendMsg( $this->tokens->getAllStr(1) );

      self::$page->show("Potential Sten html-template problems");
    }

    //
    // Check token.
    // A debug routine.  Write results to an error-string for later display.
    //
    private function checkToken($tk)
    {
      $level = 3;		// html header level ie. <h1>, <h2>, <h3>...
      $msg = '';

      $msg .= $tk->checkName();
      $msg .= $tk->checkType();

      $msg .= $tk->checkValueFileReadable();
      $msg .= $tk->checkValueGlobFilesReadable();
      $msg .= $tk->checkValueGlobFilePrefix();

      // To validate:
      // - last_mod token value?
      // - token does not have duplicate name

      if($msg != '')
        $msg = "<h$level>For token name '{$tk->getName()}'</h$level> <ul>$msg</ul> ";
      self::$page->appendMsg($msg);
    }

  }

  /////////////////////////////////////////////////////////////////////////////
  /**
   * STEN is a "Simple Template ENgine".
   *
   * StenTokens: A collection of StenToken objects
   */
  class StenTokens
  {
    // Instance vars
    /*
     * The key to the $tokens hash is the token-name.
     * This is a slightly messy implementation since token-name also appears
     * within the token object (and duplicates of the same info might cause
     * consistency problems later). However, since token-name is the natural
     * key for looking up the type and value I've decided to implement this
     * collection as a hash.  As this is a class (which hides the implementation
     * details) I can always re-write later if I find this implementation is
     * unsuitable.
     */
    private $tokens;		// A hash of token objects. Key is token-name.

    //
    // Add token obj to collection
    //
    public function add($token) { $this->tokens[ $token->getName() ] = $token; }

    //
    // Get token obj by key
    //
    public function get($key)
    {
      if( $this->keyExists($key, $this->tokens) )
        return $this->tokens[$key];
      else
        return new StenToken($key, '', '');	// Dummy token (where type is deliberately invalid)
    }

    //
    // Does key (ie. token-name) exist in collection?
    //
    public function keyExists($key) { return array_key_exists($key, $this->tokens); }

    ///////////////////////////////////////////////////////////////////////////
    // Debug functions below here
    ///////////////////////////////////////////////////////////////////////////

    //
    // Get all tokens. Return them in an HTML string.
    // A debug routine.
    //
    public function getAllStr($level=6)
    {
      global $nl;
      $str = "$nl <h$level>Showing all tokens within object</h$level>";
      $str .= "<table border=\"1\"> ";
      $str .= "<tr><td> TOKEN_NAME </td><td> TOKEN_TYPE </td><td> TOKEN_VALUE</td></tr> ";

      ksort($this->tokens);
      foreach($this->tokens as $key => $tk)
        $str .= "<tr><td> '$key' </td><td> '{$tk->getType()}' </td><td> '{$tk->getValue()}' </td></tr> ";
      $str .= "</table> ";
      return $str;
    }

  }

  /////////////////////////////////////////////////////////////////////////////
  /**
   * STEN is a "Simple Template ENgine".
   *
   * StenToken: A token object within a Sten template. Token-names are special
   * labels within a template file to be substituted. Tokens are also
   * characterised by a type (which defines *how* a token name shall be
   * substituted) and a value (which is used differently for different types
   * of token).
   */
  class StenToken
  {
    // Instance vars
    private $name;
    private $type;
    private $value;

    // List of valid token types
    private static $typeList = array(
      'text',
      'file',
      'file_glob',
      'file_tpl',
      'last_mod',
    );

    //
    // Create a token obj
    // - $name:  The token-string as it appears within the template.
    // - $type:  The type of token substitution to be applied.
    // - $value: The value of the token (which depends on the token type).
    //
    public function __construct($name, $type, $value)
    {
      $this->name = $name;
      $this->type = $type;
      $this->value = $value;
    }

    //
    // Methods to get the token attributes
    //
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getValue() { return $this->value; }

    ///////////////////////////////////////////////////////////////////////////
    // Debug functions below here
    ///////////////////////////////////////////////////////////////////////////

    //
    // To string: The string representation of a token.
    //
    public function toStr()
    {
      return "Token[name,type,value]=['$this->name', '$this->type', '$this->value']";
    }

    //
    // Check token-name.
    // Return html string giving errors, warning, notices, etc.
    //
    public function checkName()
    {
      if( !preg_match("/^[A-Za-z0-9_\.]+$/", $this->name) )
        return "<li>Token-name ERROR in {$this->toStr()} (assuming token is used). Token-name must " .
          "consist of 1 or more characters from the regular expression [A-Za-z0-9_\.]</li>";
      return '';					// OK
    }

    //
    // Check token-type.
    // Return html string giving errors, warning, notices, etc.
    //
    public function checkType()
    {
      $typeListStr = implode(', ', self::$typeList);	// Comma separated string of valid token types
      if( !in_array($this->type, self::$typeList) )
        return "<li>Token-type ERROR in {$this->toStr()} (assuming token is used). Token-type must be one of: $typeListStr.</li>";

      return '';					// OK
    }

    //
    // Check token-value: File should be readable.
    // Return html string giving errors, warning, notices, etc.
    //
    public function checkValueFileReadable()
    {
      $typeList = array(				// Token-value is a filename for these types
        'file',
        'file_tpl',
      );
      $fname = $this->value;
      if( !in_array($this->type, $typeList) )
        return '';					// OK
      if( !is_readable($fname) || !is_file($fname) )
        return "<li>Token-value ERROR in {$this->toStr()} (assuming token is used). Token-value '$fname' is not a readable file.</li>";
      return '';					// OK
    }

    //
    // Check token-value: Glob files should be readable.
    // Return html string giving errors, warning, notices, etc.
    //
    public function checkValueGlobFilesReadable()
    {
      $typeList = array(
        'file_glob',
      );
      if( !in_array($this->type, $typeList) )
        return '';					// OK
      $subst = $this->value;
      $str = '';
      foreach(glob("$subst*" . Sten::GLOB_EXT) as $fname)	// For each matching file
        if( !is_readable($fname) || !is_file($fname) )
          $str .=  "<li>Token-value (glob-prefix) ERROR in {$this->toStr()} (assuming token is used). '$fname' is not a readable file.</li>";
      return $str;
    }

    //
    // Check token-value: Glob file-prefix match should not be too wide
    // Return html string giving errors, warning, notices, etc.
    //
    public function checkValueGlobFilePrefix()
    {
      $typeList = array(
        'file_glob',
      );
      $subst = $this->value;
      if( !in_array($this->type, $typeList) )
        return '';					// OK
      if( $subst == '')
        return "<li>Token-value WARNING re {$this->toStr()} (assuming token is used). Empty token-value " .
          "(glob-prefix) will result in the large file match of '$subst*" . Sten::GLOB_EXT . "'.</li>";
      return '';					// OK
    }

  }

  /////////////////////////////////////////////////////////////////////////////
  /**
   * Simple HTML page class (for displaying errors, warnings, notices, etc)
   */
  class SimplePage
  {
    private $msg = '';

    /*
     * Show simple web page, then exit
     */
    public function appendMsg($msg) { $this->msg .= $msg; }

    /*
     * Show simple web page, then exit
     */
    public function show($title, $msg='')
    {
      $this->appendMsg($msg);

      echo <<<SIMPLE_PAGE_SHOW
        <html>
          <head>
            <title>$title</title>
          </head>
          <body>
            <h1>$title</h1>
            $this->msg
          </body>
        </html>
SIMPLE_PAGE_SHOW;
      exit(0);
    }
  }

?>

