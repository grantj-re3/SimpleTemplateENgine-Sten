<?php

  /* *************************************************************************
   * Sten
   * Written/tested using PHP 5.2
   *
   * File:	class.csvtable.php
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
  //$nl = "<br />\n";		// HTML newline

  /////////////////////////////////////////////////////////////////////////////
  /**
   * STEN is a "Simple Template ENgine".
   *
   * StenCsv: A CSV-file object (for Sten).
   */
  class StenCsv
  {
    // Constants
    const MAX_LINE_LEN = '100000';	// Max line length found in CSV file

    // Instance vars
    private $fname;		// CSV filename
    private $hasHeader;		// First line of CSV file has header? true or false
    private $delim;		// Single char delim (usually comma)
    private $cls;		// CssClass obj
    private $debug;		// Debug? true or false

    //
    // Create CSV object
    //
    public function __construct($fname, $hasHeader=true, $delim=',')
    {
      $this->fname = $fname;
      $this->hasHeader = $hasHeader;
      $this->delim = $delim;
      $this->cls = new CssClasses();
    }

    //
    // Add CssClass object to the collection
    //
    public function addCssClass($tname, $cname)
    { $this->cls->add( new CssClass($tname, $cname) ); }

    //
    // Set debug
    //
    public function setDebug($debug=true) { $this->debug = $debug; }

    //
    // Set the table object to be used by toTableStr()
    //
    public function setTable($table) { $this->table = $table; }

    //
    // Get the CSV filename
    //
    public function getFname() { return $this->fname; }

    //
    // Produce an HTML string where the CSV data appears within a table
    //
    public function toTableStr()
    {
      $nl = "\n";				// Use a global?
      $str = '';
      $row = 0;

      $fh = fopen($this->fname, 'r');		// Open CSV file
      if(!$fh)
      {
        $page = new SimplePage();
        $page->show('ERROR: Cannot open file', "File '{$this->fname}' cannot be opened for reading or does not exist");
      }
      while( $data = fgetcsv($fh, self::MAX_LINE_LEN, $this->delim) )
      {
        $row++;
        ///////////////////// PASS IN AS A PARAM $tableProperties TO CONTRUCTOR ... OR SET IN CSS ///////////////////
        if($row == 1) $str .= "<table cellspacing=\"15\"> $nl";

        $class = $this->cls->toStr("tr," . ($row % 2));	// Row-arg: Even='tr,0'. Odd='tr,1'
        ///////////////////// PASS IN AS A PARAM $tableProperties TO CONTRUCTOR ... OR SET IN CSS ///////////////////
        //$str .= "<tr $class> ";
        $str .= "<tr $class align=\"left\"> ";
        $addHeader = ($row == 1 && $this->hasHeader);	// true = add header; false = do not add header
        foreach($data as $value)
        {
          if($addHeader)
            $str .= "<th>$value</th> ";
          else
            $str .= "<td>$value</td> ";
        }
        $str .= "</tr> $nl";
      }
      if($row > 0) $str .= "</table> $nl";
      fclose($fh);
      return $str;
    }

  }

  /////////////////////////////////////////////////////////////////////////////
  /**
   * STEN is a "Simple Template ENgine".
   *
   * CssClasses: A collection of CssClass
   */
  class CssClasses
  {
    // Instance vars
    // Slightly messy implementation because tag-name appears within the
    // object and is duplicated as the key of the collection! Hence
    // potential issue re inconsistency at some future time.
    private $classes;		// A hash of CssClass objects. Key is html tag.

    //
    // Create CSS-classes object
    //
    public function __construct() { $this->classes = array(); }

    //
    // Add CssClass obj to collection
    //
    public function add($cls) { $this->classes[ $cls->getTag() ] = $cls; }

    //
    // Convert to string.
    // Eg. In the caller, string shall be inserted at $retVal in: "<tr $retVal>"
    // For <tr> $key='tr'. I recommend using the following notation for even/odd rows.
    // - For even-row <tr> set $key (ie. tag-name) = 'tr,0'.
    // - For odd-row <tr> set $key (ie. tag-name) = 'tr,1'.
    //
    public function toStr($key)
    {
      if( $this->keyExists($key, $this->classes) )
        return "class=\"{$this->classes[$key]->getClass()}\"";	// $retVal='class="CLASS_NAME"'
      else
        return '';				// $retVal=''
    }

    //
    // Does key (ie. class-name) exist in collection?
    //
    public function keyExists($key) { return array_key_exists($key, $this->classes); }

  }

  /////////////////////////////////////////////////////////////////////////////
  /**
   * STEN is a "Simple Template ENgine".
   *
   * CssClass: An object for associating a CSS-class with a HTML tag
   */
  class CssClass
  {
    // Instance vars
    private $tname;			// HTML tag name
    private $cname;			// CSS class name
    //private $debug;		// Debug? true or false

    //
    // Create CSS-class object
    //
    public function __construct($tname, $cname)
    {
      $this->tname = $tname;
      $this->cname = $cname;
    }

    //
    // Get tag name / Get class name
    //
    public function getTag() { return $this->tname; }
    public function getClass() { return $this->cname; }

  }

?>

