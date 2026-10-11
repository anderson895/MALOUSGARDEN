<?php
// Copy this file to mail-config.php and fill in the Gmail account that sends
// the registration OTP. Use a Google "App password" (Google Account >
// Security > 2-Step Verification > App passwords), not the account password.
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USER')) define('SMTP_USER', 'your-shop-account@gmail.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'your16charapppassword');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', "Malou's Garden");
