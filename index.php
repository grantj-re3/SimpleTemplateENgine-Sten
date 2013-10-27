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

  $tpl->addToken('title',        'text',      "Sten demo - home page");
  $tpl->addToken('linkCss1',     'text',      "$webSitePath/common/style.css");
  $tpl->addToken('root_path',    'text',      "$webSitePath");
  $tpl->addToken('main_content', 'file_glob', "main");
  $tpl->addToken('side_content', 'file_glob', "$webSitePath/common/side");

  //$tpl->setDebug();			// Switch on debug/verification web-page
  $tpl->show("$fsSitePath/common/template.html");	// Show the template (with tokens replaced with content)

?>
