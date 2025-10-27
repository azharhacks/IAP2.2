<?php
/**
 * User Registration Page
 * Handles new user account creation using the Layout and Forms classes
 * Renders registration form with validation and email verification setup
 */

// Load configuration and class autoloader
require 'config.php';
require 'ClassAutoload.php';

// Initialize Layout and Forms objects for rendering
$ObjLayout = new Layout();
$ObjForm = new Forms();

// Render the complete registration page structure
$ObjLayout->header($conf);        // HTML head section with meta tags and CSS
$ObjLayout->navbar($conf);       // Navigation bar component

// Use the form_content method from Layout to render registration form
$ObjLayout->form_content($conf, $ObjForm);

$ObjLayout->footer($conf);       // Page footer with copyright and links
?>
