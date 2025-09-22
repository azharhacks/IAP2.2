<?php
require 'Config.php';
require 'ClassAutoload.php';


$ObjLayout->header($conf);
$ObjLayout->navbar($conf);


// Use the form_content method from Layout
$ObjLayout->form_content($conf, $ObjForm);

$ObjLayout->footer($conf);
?>
