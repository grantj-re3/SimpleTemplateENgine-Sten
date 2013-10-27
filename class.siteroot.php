<?php
  /* *************************************************************************
   * Sten
   * Written/tested using PHP 5.2
   *
   * File:	class.siteroot.php
   * Author:	Grant Jackson
   * Package:	Sten -- Simple (HTML) Templating ENgine written in PHP.
   *
   * Copyright (C) 2010
   * Licensed under GPLv3. GNU GENERAL PUBLIC LICENSE, Version 3, 29 June 2007
   * http://www.gnu.org/licenses/
   * *************************************************************************/

  /*
   * SiteRoot: A class to find the path to this site's (filesystem and web) 
   * root-dir. It is intended that one of these will exist in EACH dir where 
   * a PHP script requires access to files relative to the root-dir (eg. Sten
   * templates, PHP class files, CSS, images, etc).
   *  - Your site root-dir might be ANYWHERE under your server's web root-dir.
   *  - SiteRoot exposes 4 methods (including the constructor) to the caller.
   *    Any re-write should attempt to expose the same methods/functionality.
   *
   * Example usage (eg. near the top of say 'index.php'):
   *   include('class.siteroot.php');		// Load SiteRoot class
   *   $sr = new SiteRoot();			// Create SiteRoot object
   *   //$sr->setDebug();			// Optionally set debug in dev/test
   *   $fsSitePath = $sr->getFsRoot();		// Get filesystem root-dir
   *   $webSitePath = $sr->getWebRoot();	// Get web root-dir
   *   include("$fsSitePath/common/class.sten.php");	// Refer to Sten class or other files
   */
  class SiteRoot
  {
    const DEFAULT_MARKER_FNAME = 'siteroot.txt';
    const MAX_DIR_DEPTH = '20';

    private $markerFname;	// Filename which marks root dir of site
    private $rootPath;		// Path to root dir

    private $isFound = false;	// Have not found root dir of site yet
    private $debug = false;

    // Constructor: Optional arg gives marker filename at top of this site
    public function __construct($markerFname = SiteRoot::DEFAULT_MARKER_FNAME)
    { $this->markerFname = $markerFname; }

    // Set the state of debug
    public function setDebug($debug=true) { $this->debug = $debug; }

    // Get this site's web-root dir
    public function getWebRoot() { return $this->getFsRoot(); }

    // Get this site's filesystem-root dir
    public function getFsRoot() { $this->setRootDir(); return $this->rootDir; }

   /*
    * Find the root-dir of this web site. The root-dir must have been marked by
    * placing a readable file (with file name given by $markerFname) in the
    * root-dir. This function searches for this marker file within the current-dir
    * then each dir above the current-dir until the marker-file is found.
    * 
    * Why the need for this function? So that you can move your web site
    * between multiple hosts (eg. development, test and production) or multiple
    * dirs on a single host without having to update hard-coded paths.
    * 
    * This algorithm gives the root dir as a relative-path. Hence it is suitable
    * for both use by the filesystem (ie. PHP) and web (ie. HTML/CSS).
    */
    private function setRootDir()
    {
      if($this->isFound) return;		// Root dir already found & set
      $markerFname = $this->markerFname;
      for($i=0, $rel_path2root = '.'; $i<SiteRoot::MAX_DIR_DEPTH; $i++, $rel_path2root .= '/..')
        if(is_readable("$rel_path2root/$markerFname"))
        {
          $this->rootDir = $rel_path2root;	// Found relative root-dir
          $this->isFound = true;
          return;
        }
      // Error: Relative root-dir has not been found yet
      $error_msg = "The web-site root-directory has not been marked";
      if($this->debug)
        $error_msg .= " by placement of a readable file '$markerFname' " .
          "at or above directory: <br /> <pre>" . getcwd() . "</pre>";
      $this->showPage('ERROR: Web site root directory not found!', $error_msg);
    }

    // Show simple web page, then exit
    private function showPage($title, $message)
    {
      echo <<<SR_SIMPLE_PAGE
        <html>
          <head>
            <title>$title</title>
          </head>
          <body>
            <h2>$title</h2>
            $message
          </body>
        </html>
SR_SIMPLE_PAGE;
      exit(0);
    }
  }

?>
