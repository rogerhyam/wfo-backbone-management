<?php

// takes the contents of /cli and build a phar in the www directory so it can be included in the build of the whole application.

// The php.ini setting phar.readonly must be set to 0
$pharFile = '../www/rhakhis.phar';

// clean up
if (file_exists($pharFile)) unlink($pharFile);
if (file_exists($pharFile . '.gz')) unlink($pharFile . '.gz');

// create phar
$p = new Phar($pharFile);
// creating our library using whole directory
$p->buildFromDirectory('../cli/');

// pointing main file which requires all classes
$p->setDefaultStub('main.php');

echo "$pharFile successfully created\n";
