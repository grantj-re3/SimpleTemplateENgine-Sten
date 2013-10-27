SimpleTemplateENgine-Sten 
=========================

Purpose
-------
A simple (HTML) template engine written in PHP. If your Internet Service Provider offers you some web space and PHP, this tool will allow you to create and maintain a web-site of static-like web pages but with content cleanly separated from both style and boiler-plate menus, sidebars, etc.

Application environment
-----------------------
Read the INSTALL file.

Installation
------------
Read the INSTALL file.

Features
--------
* Allows a consistent look across your website.
* Allows special tokens within your web page to be replaced with the following.
  * A text string.
  * The contents of a file.
  * The contents of all files matching PREFIX*.inc.html.
  * The content of a file template (ie.  similar to 'file' above except the specified file can contain tokens which will also be replaced).
  * A string representing the last modified date-time.
* Helpers to assist you to move your website from one server to another (eg. from a development environment to a test environment or from test to production).

Todo
----
Complete class.csvtable.php

