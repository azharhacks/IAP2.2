<?php
require 'Config.php';
require 'ClassAutoload.php';

$ObjLayout->header($conf);
$ObjLayout->navbar($conf);
$ObjLayout->banner($conf);
$ObjLayout->content($conf);
$ObjLayout->footer($conf);
?>