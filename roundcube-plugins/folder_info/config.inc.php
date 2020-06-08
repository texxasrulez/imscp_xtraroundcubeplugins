<?php
##
## Example configuration file
##

// custom messages for folders
/*
$config['folder_info_messages'] = array(
   'INBOX.Trash' => 'Messages will be deleted after {} {}.',
   'INBOX.Junk' => 'Messages will be deleted after {} days.'
);
*/

// arguments for messages
$config['folder_info_messages_args'] = array(
  'INBOX.Trash' => array(30, 'days'),
  'INBOX.Junk' => 7
);

?>