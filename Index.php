<?php
/**
 * Homepage/Index Page
 * Main landing page of the application using the Layout and Forms classes
 * Displays welcome content, navigation, and general site information
 */

// Load configuration and class autoloader
require 'config.php';
require 'ClassAutoload.php';

// Render the complete homepage structure
$ObjLayout ->header($conf);      // HTML head section with meta tags and CSS
$ObjLayout ->navbar($conf);     // Navigation bar component
$ObjLayout ->banner($conf);     // Page banner/hero section
$ObjLayout ->content($conf, $ObjForm);  // Main homepage content
$ObjLayout ->footer($conf);     // Page footer with copyright and links
?>
