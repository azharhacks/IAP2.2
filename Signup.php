<?php
require 'config.php';
require 'ClassAutoload.php';

// Create Layout and Forms instances
$ObjLayout = new Layout();
$ObjForm = new Forms();

$ObjLayout->header($conf);
$ObjLayout->navbar($conf);

// Use the form_content method from Layout
$ObjLayout->form_content($conf, $ObjForm);

$ObjLayout->footer($conf);
?>
