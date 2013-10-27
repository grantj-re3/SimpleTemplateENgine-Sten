<?php
  // find the site root
  require_once('class.siteroot.php');		// Load SiteRoot class
  $sr = new SiteRoot();			// Create SiteRoot object
  //$sr->setDebug();			// Optionally set debug in dev/test
  $fsSitePath = $sr->getFsRoot();	// Get filesystem root-dir
  $webSitePath = $sr->getWebRoot();	// Get web root-dir


  // display using webtemplate
  require_once("$fsSitePath/common/class.sten.php");	// Refer to Sten class or other files

  $tpl = new Sten();			// Create template obj

  $tpl->setTimezone('Australia/South');
  $tpl->setLastMod(filemtime("$_SERVER[SCRIPT_FILENAME]"),'D j F Y -- H:i:s');	// Supply last mod time of caller
  $tpl->addToken('modified','last_mod', "");

  $tpl->addToken('title',        'text',      "Sten demo - page 2");
  $tpl->addToken('linkCss1',     'text',      "$webSitePath/common/style.css");
  $tpl->addToken('root_path',    'text',      "$webSitePath");
  $tpl->addToken('main_content', 'file_tpl',  "demo_child.tpl.html");
  $tpl->addToken('side_content', 'file_glob', "$webSitePath/common/side");

  $str = "<h3>Sten 'text' feature</h3>\n\t<p>A Sten 'text' token is producing this section of html. That is, the token is being replaced with a variable or constant text-string.</p>\n";
  $tpl->addToken('demo_text',    'text',      "$str");
  $tpl->addToken('demo_file',    'file',      "demo_file.html");

  // Populate the StenCsv object (for use with a 'file_csv' token-type)
  require_once("$fsSitePath/common/class.csvtable.php");        // Get Sten CSV to Table converter
  $csv = new StenCsv('demo_population_table.csv', true, '|');
  $tpl->addToken('demo_csv_table',    'file_csv',     $csv);  // Token value is a StenCsv obj


  //$tpl->setDebug();			// Switch on debug/verification web-page
  $tpl->show("$fsSitePath/common/template.html");	// Show the template (with tokens replaced with content)

?>
