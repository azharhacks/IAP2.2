<?php
/**
 * User Sign-in Page
 * Entry point for user authentication using the Layout and Forms classes
 * Renders the complete sign-in interface with header, navbar, banner, and form
 */

// Load configuration and class autoloader
require 'config.php';
require 'ClassAutoload.php';

// Initialize Layout and Forms objects for rendering
$ObjLayout = new Layout();
$ObjForm = new Forms();

// Render the complete sign-in page structure
$ObjLayout->header($conf);        // HTML head section with meta tags and CSS
$ObjLayout->navbar($conf);        // Navigation bar component
$ObjLayout->banner($conf);        // Page banner/hero section
$ObjLayout->form_content($conf, $ObjForm);  // Main sign-in form content
$ObjLayout->footer($conf);        // Page footer with copyright and links
?>