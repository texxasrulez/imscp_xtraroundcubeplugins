<?php
/* Password encryption Methods:
    'rcmail': encrypt passwords by default Roundcube methods.
    'caldav': passwords must be re-entered by users.
*/
$config['additional_imap_crypt'] = 'rcmail';

/* password encryption salt (only used for caldav encryption method) */
$config['additional_imap_salt'] = '4JS3Vadi9t+MeVzAAk3bHKXTY6Znyt';

/* predefined imap hosts (associated with the domain part of the identity email property) */
$config['additional_imap_external'] = array(
  'gmail.com' => array(
    'host' =>'ssl://imap.gmail.com:993',
    'delimiter' => '/',
    'default_folders' => array('INBOX'),
    'readonly' => true, // on match prevent field editing
  ),
  'yahoo.com' => array(
    'host' =>'ssl://imap.mail.yahoo.com:993',
    'delimiter' => '/',
    'default_folders' => array('INBOX'),
    'readonly' => true, // on match prevent field editing
  ),
  'hotmail.com' => array(
    'host' =>'ssl://outlook.office365.com:993',
    'delimiter' => '/',
    'default_folders' => array('INBOX'),
    'readonly' => true, // on match prevent field editing
  ),
);
/*
$config['additional_imap_internal'] = array(
  'mydomain.tld' => array(
    'host' =>'ssl://imap.mydomain.tld:993',
    'delimiter' => '/',
    'readonly' => true, // on match prevent field editing
  ),
);
*/

/* auto-detect IMAP server */
$config['additional_imap_autodetect'] = false;

/* Cache remote accounts
   NOTE: if you enable this option your database user must have permissions to CREATE and DROP database tables */
$config['additional_imap_cache'] = false;

/* Cache garbage collection
   Remove unused cache tables every x-nd request (randomly) */
$config['additional_imap_gc'] = 100;

?>