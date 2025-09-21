<?php
require 'Config.php';
require 'ClassAutoload.php';
require 'Abstract/Layout.php';

// Create layout instance
$ObjLayout = new Layout();
$ObjLayout->header($conf);
$ObjLayout->navbar($conf);

// Create Forms instance
$ObjForms = new Forms();

// Use the form_content method from Layout
$ObjLayout->form_content($conf, $ObjForms);

$ObjLayout->footer($conf);
?>
