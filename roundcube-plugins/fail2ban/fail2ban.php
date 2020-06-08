<?php
/**
 * RoundCube Fail2Ban Plugin
 *
 * @version 1.3
 * @author Matt Rude [m@mattrude.com]
 * @url http://mattrude.com/plugins/roundcube-fail2ban-plugin/
 * @license GPLv3
 */
class fail2ban extends rcube_plugin
{
  function init()
  {
    $this->add_hook('login_failed', array($this, 'log'));
  }

  function log($args)
  {
    $remote_addr = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    rcmail::write_log('userlogins', 'FAILED login for ' .$args['user']. ' from ' .$remote_addr);
  }
}
