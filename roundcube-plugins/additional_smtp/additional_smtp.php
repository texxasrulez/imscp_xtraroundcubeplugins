<?php
class additional_smtp extends rcube_plugin {
    var $task = 'mail|settings';
    private $rcmail;

    function init() {
        $this->rcmail = rcmail::get_instance();
        $this->load_config();
        $this->add_hook('identity_form', array($this, 'identity_form'));
        $this->add_hook('identity_update', array($this, 'identity_update'));
        $this->add_hook('identity_delete', array($this, 'identity_delete'));
        $this->add_hook('message_compose_body', array($this, 'message_compose_body'));
        $this->add_hook('message_outgoing_headers', array($this, 'message_outgoing_headers'));
        $this->add_hook('smtp_connect', array($this, 'smtp_connect'));
        $this->add_hook('password_change', array($this, 'password_change'));
    }

    function password_change($args) {
        $rcmail = $this->rcmail;
        if ($rcmail->config->get('additional_smtp_crypt') == 'secure') {
            $sql = 'SELECT * FROM ' . rcmail::get_instance()->db->table_name('additional_smtp').
            ' WHERE user_id=?';
            $res = $rcmail->db->query($sql, $rcmail->user->ID);
            $conf = array();
            while ($sql_arr = $rcmail->db->fetch_assoc($res)) {
                $conf[] = $sql_arr;
            }
            foreach($conf as $sql_arr) {
                $decrypt = $this->decrypt($sql_arr['password'], $args['old_pass'], $rcmail->config->get('additional_smtp_salt', '!!!!Random_1_2_4_5_6_String!!!!'));
                $decrypt = $this->encrypt($decrypt, $args['new_pass'], $rcmail->config->get('additional_smtp_salt', '!!!!Random_1_2_4_5_6_String!!!!'));
                $sql = 'UPDATE ' . rcmail::get_instance()->db->table_name('additional_smtp').
                ' SET password=? WHERE user_id=? AND iid=?';
                $rcmail->db->query($sql, $decrypt, $rcmail->user->ID, $sql_arr['iid']);
            }
        }
        return $args;
    }

    function message_compose_body($args) {
        $rcmail = rcmail::get_instance();
        if ($rcmail->user->data['username'] != $_SESSION['username']) {
            $this->sender = $_SESSION['username'];
            $this->smtp_overwrite = true;
            $sql_arr = $this->smtp_connect(array());
            $rcmail->config->set('no_save_sent_messages', $sql_arr['nosavesent'] ? true : false);
        }
        return $args;
    }

    function message_outgoing_headers($args) {
        $sender = explode('@', $args['headers']['X-Sender'], 2);
        $rc_uname = explode('@', $this->rcmail->user->data['username'], 2);
        $smtp_user = $this->rcmail->config->get('smtp_user');
        if ($smtp_user != '%u' || strtolower($sender[1]) != strtolower($rc_uname[1])) {
            $this->smtp_overwrite = true;
            $this->sender = $args['headers']['X-Sender'];
        }
        if (!$this->rcmail->config->get('smtp_server')) {
            $sql_arr = $this->smtp_connect(array());
            if (count($sql_arr > 0)) {
                $this->rcmail->config->set('smtp_user', $sql_arr['smtp_user']);
                $this->rcmail->config->set('smtp_pass', $sql_arr['smtp_pass']);
                $this->rcmail->config->set('smtp_port', $sql_arr['smtp_port']);
                $this->rcmail->config->set('smtp_server', $sql_arr['smtp_server']);
                $this->rcmail->config->set('no_save_sent_messages', $sql_arr['nosavesent'] ? true : false);
            }
        }
        return $args;
    }

    function smtp_connect($args) {
        $rcmail = $this->rcmail;
        if ($this->smtp_overwrite) {
            $sender = explode('@', $this->sender, 2);
            $rc_uname = explode('@', $this->rcmail->user->data['username'], 2);
            $smtp_int = $rcmail->config->get('additional_smtp_internal', array());
            if ($instance = $smtp_int[strtolower($sender[1])]) {
                $smtp_url = parse_url($instance['host']);
                if ($smtp_url['port']) {
                    $instance['smtp_port'] = $smtp_url['port'];
                }
                $instance['smtp_server'] = $instance['host'];
                unset($instance['host']);
                unset($instance['readonly']);
                $args = array_merge($args, $instance);
            } else {
                $sql = 'SELECT identity_id FROM ' . rcmail::get_instance()->db->table_name('identities') . ' WHERE user_id=? AND email=? AND del=? LIMIT 1';
                $res = $rcmail->db->query($sql, $rcmail->user->ID, $this->sender, 0);
                $argsv = array();
                while ($fetch = $rcmail->db->fetch_assoc($res)) {
                    $argsv[] = $fetch;
                }
                foreach($argsv as $fetch) {
                    $sql = 'SELECT * FROM ' . rcmail::get_instance()->db->table_name('additional_smtp') . ' WHERE user_id=? AND iid=? LIMIT 1';
                    $res = $rcmail->db->query($sql, $rcmail->user->ID, $fetch['identity_id']);
                    $sql_arr = $rcmail->db->fetch_assoc($res);
                    if (is_array($sql_arr) && $sql_arr['enabled']) {
                        $smtp_url = parse_url($sql_arr['server']);
                        if ($smtp_url['port']) {
                            $sql_arr['smtp_port'] = $smtp_url['port'];
                        }
                        $sql_arr['smtp_server'] = $sql_arr['server'];
                        $sql_arr['smtp_user'] = $sql_arr['username'];
                        $decrypt = $this->decrypt($sql_arr['password'], $rcmail->decrypt($_SESSION['password']), $rcmail->config->get('additional_smtp_salt', '!!!!Random_1_2_4_5_6_String!!!!'));
                        $sql_arr['smtp_pass'] = $decrypt;
                        $args = array_merge($args, $sql_arr);
                        $rcmail->config->set('no_save_sent_messages', $sql_arr['nosavesent'] ? true : false);
                        break;
                    }
                }
            }
        }
        if (strtolower($rcmail->user->data['username']) != strtolower($_SESSION['username'])) {
            if ($args['smtp_user'] == '%u') {
                $args['smtp_user'] = $rcmail->user->data['username'];
            }
            if ($args['smtp_pass'] == '%p') {
                $args['smtp_pass'] = $_SESSION['default_account_password'] ? $_SESSION['default_account_password'] : $_SESSION['password'];
                $args['smtp_pass'] = $rcmail->decrypt($args['smtp_pass']);
            }
        }
        return $args;
    }

    function identity_form($args) {
        $rcmail = $this->rcmail;
        if ($rcmail->action == 'edit-identity') {
            $this->include_script('additional_smtp.js');
            $this->add_texts('localization/');
            $sql = 'SELECT * from ' . rcmail::get_instance()->db->table_name('additional_smtp') . ' WHERE iid=? AND user_id=?';
            $res = $rcmail->db->query($sql, $args['record']['identity_id'], $rcmail->user->ID);
            $data = 'data';
            $ident_form = true;
            if ($sql_arr = $rcmail->db->fetch_assoc($res)) {
                $server = $sql_arr['server'];
                $user = $sql_arr['username'];
                $pass = $sql_arr['password'];
                $no_sav = $sql_arr['nosavesent'] ? true : false;
                $smtp_enabled = $sql_arr['enabled'] ? true : false;
                $sender = explode('@', $args['record']['email'], 2);
                $rc_uname = explode('@', $this->rcmail->user->data['username'], 2);
                $smtp_ext = $rcmail->config->get('additional_smtp_external', array());
                $smtp_int = $rcmail->config->get('additional_smtp_internal', array());
                if ($instance = $smtp_int[strtolower($sender[1])]) {
                    $ident_form = false;
                } else if ($instance = $smtp_ext[strtolower($sender[1])]) {
                    $server = $instance['host'];
                    if ($instance['readonly']) {
                        $data = 'readonly';
                    }
                }
            } else {
                $smtp_enabled = false;
                $user = $args['record']['email'];
                $sender = explode('@', $args['record']['email'], 2);
                $rc_uname = explode('@', $this->rcmail->user->data['username'], 2);
                $smtp_ext = $rcmail->config->get('additional_smtp_external', array());
                $smtp_int = $rcmail->config->get('additional_smtp_internal', array());
                if ($instance = $smtp_int[strtolower($sender[1])]) {
                    $ident_form = false;
                    $no_sav = $instance['no_save_sent_messages'];
                } else if ($instance = $smtp_ext[strtolower($sender[1])]) {
                    $server = $instance['host'];
                    if ($instance['readonly']) {
                        $data = 'readonly';
                    }
                    $no_sav = $instance['no_save_sent_messages'];
                } else {
                    $no_sav = false;
                    $sql = 'SELECT * FROM ' . rcmail::get_instance()->db->table_name('additional_smtp_hosts') . ' WHERE domain=? LIMIT 1';
                    $ds = $rcmail->db->query($sql, $sender[1]);
                    $ds = $rcmail->db->fetch_assoc($ds);
                    if (is_array($ds) && strtotime($ds['ts']) + 86400 * 7 > time()) {
                        $server = $ds['host'];
                    } else {
                        if (function_exists('getmxrr') && $rcmail->config->get('additional_smtp_autodetect', false)) {
                            if (@getmxrr($sender[1], $DS, $v)) {
                                $auto_sender = array();
                                foreach($v as $y => $w) {
                                    $auto_sender[$w] = $DS[$y];
                                }
                                ksort($auto_sender);
                                if (!empty($auto_sender)) {
                                    $server = current($auto_sender);
                                    $L = @fsockopen('ssl://'.$server, 465, $n, $h, 5);
                                    if ($L) {
                                        fclose($L);
                                        $server = 'ssl://'.$server.
                                        ':465';
                                    } else {
                                        $L = @fsockopen($server, 587, $n, $h, 5);
                                        if ($L) {
                                            fclose($L);
                                            $server.= ':587';
                                        } else {
                                            $L = @fsockopen($server, 25, $n, $h, 5);
                                            if ($L) {
                                                fclose($L);
                                                $server.= ':25';
                                            } else {
                                                $server = null;
                                            }
                                        }
                                    }
                                    $sql = 'DELETE FROM ' . rcmail::get_instance()->db->table_name('additional_smtp_hosts') . ' WHERE host=?';
                                    $rcmail->db->query($sql, $server);
                                    $sql = 'INSERT INTO ' . rcmail::get_instance()->db->table_name('additional_smtp_hosts') . ' (domain, host, ts) VALUES (?, ?, ?)';
                                    $rcmail->db->query($sql, $sender[1], $server, date('Y-m-d H:i:s'));
                                }
                            }
                        } else {
                            $server = null;
                        }
                    }
                }
            }
            if ($ident_form) {
                if ($pass) {
                    $i = $this->gettext('isset');
                } else {
                    $i = $this->gettext('isnotset');
                }
                $x = array('additional_smtp.enabled' => array('type' => 'checkbox'), 'additional_smtp.smtpserver' => array('type' => 'text', 'size' => 40, 'placeholder' => $this->gettext('ie') . ' ssl://smtp.gmail.com:465', $data => $data), 'additional_smtp.smtpuser' => array('type' => 'text', 'size' => 40, 'placeholder' => $this->gettext('username')), 'additional_smtp.smtppass' => array('type' => 'password', 'size' => 40,'placeholder' => $i), 'additional_smtp.nosavesent' => array('type' => 'checkbox'), );
                $args['form']['smtp'] = array('name' => $this->gettext('additional_smtp.smtp'), 'content' => $x);
                $args['record']['additional_smtp.smtpserver'] = $server;
                $args['record']['additional_smtp.smtpuser'] = $user;
                $args['record']['additional_smtp.enabled'] = $smtp_enabled;
                $args['record']['additional_smtp.nosavesent'] = $no_sav;
            }
        }
        return $args;
    }

    function identity_update($args) {
        $rcmail = $this->rcmail;
        if ($server = rcube_utils::get_input_value('_additional_smtp_smtpserver', rcube_utils::INPUT_POST)) {
            $user = rcube_utils::get_input_value('_additional_smtp_smtpuser', rcube_utils::INPUT_POST);
            $smtp_enabled = rcube_utils::get_input_value('_additional_smtp_enabled', rcube_utils::INPUT_POST) ? 1 : 0;
            $no_sav = rcube_utils::get_input_value('_additional_smtp_nosavesent', rcube_utils::INPUT_POST) ? 1 : 0;
            if ($pass = trim(rcube_utils::get_input_value('_additional_smtp_smtppass', rcube_utils::INPUT_POST, true))) {
                $pass = $this->encrypt($pass, $rcmail->decrypt($_SESSION['password']), $rcmail->config->get('additional_smtp_salt', '!!!!Random_1_2_4_5_6_String!!!!'));
            }
            $sql = 'SELECT * from ' . rcmail::get_instance()->db->table_name('additional_smtp').
            ' WHERE iid=? AND user_id=?';
            $res = $rcmail->db->query($sql, $args['id'], $rcmail->user->ID);
            if ($sql_arr = $rcmail->db->fetch_assoc($res)) {
                if ($pass) {
                    $sql = 'UPDATE ' . rcmail::get_instance()->db->table_name('additional_smtp').
                    ' SET username=?, password=?, server=?, nosavesent=?, enabled=? WHERE user_id=? AND iid=?';
                    $rcmail->db->query($sql, $user, $pass, $server, $no_sav, $smtp_enabled, $rcmail->user->ID, $args['id']);
                } else {
                    $sql = 'UPDATE ' . rcmail::get_instance()->db->table_name('additional_smtp').
                    ' SET username=?, server=?, nosavesent=?, enabled=? WHERE user_id=? AND iid=?';
                    $rcmail->db->query($sql, $user, $server, $no_sav, $smtp_enabled, $rcmail->user->ID, $args['id']);
                }
            } else {
                $sql = 'INSERT INTO ' . rcmail::get_instance()->db->table_name('additional_smtp').
                ' (username, password, server, user_id, iid, nosavesent, enabled) VALUES (?, ?, ?, ?, ?, ?, ?)';
                $rcmail->db->query($sql, $user, $pass, $server, $rcmail->user->ID, $args['id'], $no_sav, $smtp_enabled);
            }
        }
        return $args;
    }

    function identity_delete($args) {
        $rcmail = $this->rcmail;
        $sql = 'DELETE from ' . rcmail::get_instance()->db->table_name('additional_smtp') . ' WHERE iid=? AND user_id=?';
        $rcmail->db->query($sql, $args['id'], $rcmail->user->ID);
        return $args;
    }

    function encrypt($trim_crypt, $decrypt, $sql_arr) {
        $rcmail = $this->rcmail;
		$method = 'AES-256-CBC';
        if ($rcmail->config->get('additional_smtp_crypt', 'rcmail') == 'rcmail') {
            return $rcmail->encrypt($trim_crypt);
        } else {
            $hash = hash('SHA256', $sql_arr . $decrypt, true);
            srand();
            $mcryptit = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
            if (strlen($r = rtrim(base64_encode($mcryptit), '=')) != 22) {
                return false;
            }
            $base_mycrpt = base64_encode(openssl_encrypt($hash, $trim_crypt.md5($trim_crypt), $method, $mcryptit));
            return $r . $base_mycrpt;
        }
    }

    function decrypt($base_mycrpt, $decrypt, $sql_arr) {
        $rcmail = $this->rcmail;
		$method = 'AES-256-CBC';
        if ($rcmail->config->get('additional_smtp_crypt', 'rcmail') == 'rcmail') {
            return $rcmail->decrypt($base_mycrpt);
        } else {
            $hash = hash('SHA256', $sql_arr.$decrypt, true);
            $mcryptit = base64_decode(substr($base_mycrpt, 0, 22).
                '==');
            $base_mycrpt = substr($base_mycrpt, 22);
            $trim_crypt = rtrim(openssl_decrypt($method, $hash, base64_decode($base_mycrpt), $method, $mcryptit), "\0\4");
            $q = substr($trim_crypt, -32);
            $trim_crypt = substr($trim_crypt, 0, -32);
            if (md5($trim_crypt) != $q) {
                return false;
            }
            return $trim_crypt;
        }
    }
}
