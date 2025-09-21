<?php
require 'Config.php';
require 'ClassAutoload.php';

$ObjLayout ->header($conf);
$ObjLayout ->navbar($conf);
$ObjLayout ->banner($conf);
$ObjLayout ->form_content($conf, $ObjForms);
$ObjLayout ->footer($conf);
?>