<?php
require 'Config.php';
require 'ClassAutoload.php';
require 'Abstract/Layout.php';

// Create layout instance
$layout = new Layout();
$layout->header($conf);
$layout->navbar($conf);

// Create Forms instance
$forms = new Forms();

// Use the form_content method from Layout
$layout->form_content($conf, $forms);

$layout->footer($conf);
?>
