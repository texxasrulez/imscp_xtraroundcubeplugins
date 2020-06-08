<?php
/* Password encryption Methods:
    'rcmail': encrypt passwords by default Roundcube methods.
    'caldav': passwords must be re-entered by users.
*/
$config['additional_smtp_crypt'] = 'rcmail';

/* password encryption salt (only used for caldav encryption method) */
$config['additional_smtp_salt'] = '4JS3Vadi9t+MeVzAAk3bHKXTY6Znyt';

/* predefined smtp hosts (associated with the domain part of the identity email property) */
$config['additional_smtp_external'] = array(
  'gmail.com' => array(
    'host' =>'smtp.gmail.com:465',
    'no_save_sent_messages' => true, // Google saves sent mail in user Sentbox
    'readonly' => true, // on match prevent field editing
  ),
  'yahoo.com' => array(
    'host' =>'smtp.mail.yahoo.com:465',
    'smtp_helo_host' => 'smtp.genesworld.net', // Used for SMTP Relays
    'no_save_sent_messages' => true, // Yahoo saves sent mail in user Sentbox
    'readonly' => true, // on match prevent field editing
  ),
  'hotmail.com' => array(
    'host' =>'outlook.office365.com:587',
    'no_save_sent_messages' => true, // Yahoo saves sent mail in user Sentbox
    'readonly' => true, // on match prevent field editing
  ),
);

/* local smtp hosts (associated with the domain part of identitiy email property) */
/*
$config['additional_smtp_internal'] = array(
  'mydomain1.tld' => array(
    'host' => 'smtp.mydomain1.tld',
    'smtp_user' => '%u',
    'smtp_pass' => '%p',
    'smtp_helo_host' => 'smtp.mydomain1.tld',
    'smtp_auth_type' => '',
    'no_save_sent_messages' => false,
    'readonly' => true,
  ),
  'mydomain2.tld' => array(
    'host' => 'ssl://smtp.mydomain2.tld:465',
    'smtp_user' => '%u',
    'smtp_pass' => '%p',
    'smtp_helo_host' => 'smtp.mydomain2.tld',
    'smtp_auth_type' => '',
    'no_save_sent_messages' => false,
    'readonly' => false,
  ),
);
*/

/* auto-detect SMTP server */
$config['additional_smtp_autodetect'] = false;
?>