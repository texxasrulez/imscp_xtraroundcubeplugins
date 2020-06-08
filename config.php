<?php
/**
 * Forked by texxasrulez for Roundcube >= v 1.4.x with more plugins
 *
 * Original author credits:
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

return array(
	
    // acl plugin (default: yes)
    // Possible values: yes, no
    'acl_plugin' => 'yes',
	
    // account details plugin (default: yes)
    // Possible values: yes, no
    'account_details_plugin' => 'yes',
	
    // adds multiple IMAP Accounts to Roundcube Possible values: yes, no
    'additional_imap_plugin' => 'yes',
	
    // adds originating IP address for the emails sent via Roundcube (Default: yes)
    // Possible values: yes, no
    'additional_message_headers_plugin' => 'yes',
	
    // adds multiple SMTP Accounts to Roundcube Possible values: yes, no
    'additional_smtp_plugin' => 'yes',
	
    // adds advanced search for Roundcube (Default: yes)
    // Possible values: yes, no
    'advanced_search_plugin' => 'yes',

    // archive plugin (default: yes)
    // Possible values: yes, no
    'archive_plugin' => 'yes',
	
    // adds auth status for Roundcube (Default: yes)
    // Possible values: yes, no
    'authres_status_plugin' => 'yes',
	
	// Authres Configuration Parameters
	'authres_status_config' => array(
        'enable_authres_status_column' => true
	),

    // calendar plugin (default: yes)
    // Possible values: yes, no
    'calendar_plugin' => 'yes',

    // Calendar Configuration Parameters
    'calendar_config' => array(	
        // Calendar backend type - Can be multiple drivers ("database", "caldav") future ("database", "kolab", "caldav", "ical", "ldap")
        'calendar_driver' => 'caldav',
		
		// Calendar Default Driver ("database", "caldav")
		'calendar_driver_default' => 'caldav',
		
		// Pre-installed calendars, added at first access to calendar section
		// Caldav driver only
		'caldav_url' => 'https://www.domain.ltd/nextcloud/remote.php/dav/calendars/%u/',

        // Calendar view (agendaDay, agendaWeek, month)
        'calendar_default_view' => 'month',
		
		// first day of the week (0-6)
		'calendar_first_day' => 0,

        // Calendar Sync Interval value: seconds
        'calendar_sync_period' => '600',

        // Calendar Crypt Key - use `pwgen -s 24` in linux terminal for a good random key
        'calendar_crypt_key' => 'random24bytestring',
		
        // Caldav Debug Possible values: true, false
        'calendar_caldav_debug' => false,
		
        // iCal Debug Possible values: true, false
        'calendar_ical_debug' => false
    ),

    // carddav plugin (default: yes)
    // Possible values: yes, no
    'carddav_plugin' => 'yes',

    // carddav configuration parameters
    'carddav_config' => array(
		
		// Name to Display for Contact List
		'carddav_name' => 'Cloud',
		
		// URL to your addressbooks
		'carddav_url' => 'https://www.domain.ltd/nextcloud/remote.php/dav/addressbooks/users/%u/',

		// optional attributes
		'carddav_active' => true,
		'carddav_readonly' => false,
		
		// Refresh interval (Time Format HH:MM:SS)
		'carddav_refresh_time' => '01:00:00',

		// Enable a workaround for broken sync-collection support in the
		// server. RFC 6578 specifies the "sync-collection" method for
		// synchronizing collections of things over WebDAV. It is more
		// efficient -- but also more complicated -- than simply retrieving
		// the whole collection again as necessary. As a result, some server
		// implementations are buggy. Specifically DAViCal and Radicale are
		// known to have problems. If changes (updates, deletions) from one
		// connection do not sync to another, you can try enabling this
		// workaround to revert to the inefficient-but-simple method.
		'sync_collection_workaround' => false
	),
	
    // contextmenu plugin (default: yes)
    // Possible values: yes, no
    'contextmenu_plugin' => 'yes',

    // contextmenu folder plugin (default: yes)
    // Possible values: yes, no
    'contextmenu_folder_plugin' => 'yes',

    // easy unsubscribe plugin (default: yes)
    // Possible values: yes, no
    'easy_unsubscribe_plugin' => 'yes',

    // emoticons plugin (default: yes)
    // Possible values: yes, no
    'emoticons_plugin' => 'yes',

    // enigma plugin (default: yes)
    // Possible values: yes, no
    'enigma_plugin' => 'no',
	
	// Enigma Configuration Parameters
	'enigma_config' => array(
		// REQUIRED! Keys directory for all users.
		// Must be writeable by PHP process, and not in the web server document root
		'enigma_pgp_homedir' => '/usr/bin/keys'
	),

    // fail2ban plugin (default: yes)
    // Possible values: yes, no
    'fail2ban_plugin' => 'yes',

    // folder info plugin (default: yes)
    // Possible values: yes, no
    'folder_info_plugin' => 'yes',

    // keyboard_shortcuts plugin (default: yes)
    // Possible values: yes, no
    'keyboard_shortcuts_plugin' => 'yes',

    // managesieve plugin (default: no)
    // Possible values: yes, no
    'managesieve_plugin' => 'yes',

    // Configuration parameters for the managesieve plugin
    'managesieve_config' => array(
        // Enables separate management interface for vacation responses (out-of-office) (default: 1)
        // 0 - no separate section,
        // 1 - add Vacation section (default),
        // 2 - add Vacation section, but hide Filters section (no additional managesieve filters)
        'managesieve_vacation' => '1',

		// Enables separate management interface for setting forwards (redirect to and copy to)
		// 0 - no separate section (default),
		// 1 - add Forward section,
		// 2 - add Forward section, but hide Filters section
		'managesieve_forward' => '1',

        // The name of the script which will be used when there's no user script (default: managesieve)
        'managesieve_script_name' => 'managesieve'
    ),

    // message_highlight plugin (default: yes)
    // Possible values: yes, no
    'message_highlight_plugin' => 'yes',

    // new_user_dialog plugin (default: yes)
    // Possible values: yes, no
    'new_user_dialog_plugin' => 'yes',

    // newmail_notifier plugin (default: yes)
    // Possible values: yes, no
    'newmail_notifier_plugin' => 'yes',

    // Configuration parameters for the newmail_notifier plugin
    'newmail_notifier_config' => array(
        // Enables basic notification (default: true)
        // Possible values: true, false
        'newmail_notifier_basic' => true,

        // Enables sound notification (default: false)
        // Possible values: true, false
        'newmail_notifier_sound' => false,

        // Enables desktop notification (default: false)
        // Possible values: true, false
        'newmail_notifier_desktop' => false
    ),

    // NextCloud embed within Roundcube plugin (default: yes)
    'nextcloud_plugin' => 'yes',
	
	// Configuration parameters for Nextcloud RC Embed
    'nextcloud_config' => array(	
        // Define NextCloud URL
	    // URLs to nextCloud instance (use same url as rc to avoid cross domains problems) // Trailing Slash Needed
        'nextcloud_url' => 'https://www.domain.ltd/nextcloud//',

        // NextCloud Crypt Key (must match nextcloud config key)
        'roundcube_nextcloud_des_key' => 'random24bytestring'
    ),	

    // odfviewer plugin (default: yes)
    // Possible values: yes, no
    'odfviewer_plugin' => 'yes',

    // password plugin (default: yes)
    // Possible values: yes, no
    'password_plugin' => 'yes',

    // Configuration parameters for the password plugin
    'password_config' => array(
        // Determine whether current password is required to change password (default: true)
        // Possible values: true, false
        'password_confirm_current' => false,

        // Require the new password to be of a certain length (default: 6)
        // Possible values: A number or blank to allow passwords of any length
        'password_minimum_length' => 6,

        // Require the new password to contain a letter and punctuation character (default: false)
        // Possible values: true, false
        'password_require_nonalpha' => false,

        // Enables forcing new users to change their password at their first login (default: false)
        // Possible values: true, false
        'password_force_new_user' => false
    ),

    // pdfviewer plugin (default: yes)
    // Possible values: yes, no
    'pdfviewer_plugin' => 'yes',

    // persistent_login (default: yes)
    // Possible values: yes, no
    'persistent_login_plugin' => 'yes',

    // quota plugin (default: yes)
    // Possible values: yes, no
	'quota_plugin' => 'yes',
	
	// Quota Configuration Parameters
    'quota_config' => array(
		// Webmaster Email
		'quota_config' => '<a href="mailto:webmaster@domain.ltd">Email Webmaster</a>',
		// Show Webmaster Email
		'show_admin_contact' => true
	),

    // rcguard plugin (default: no)
    // Possible values: yes, no
    'rcguard_plugin' => 'no',

    // Configuration parameter for the rcguard plugin
    'rcguard_config'  => array(
        // Public key for reCAPTCHA
        'recaptcha_publickey' => '',

        // Private key for reCAPTCHA
        'recaptcha_privatekey' => '',

        // Number of failed logins before reCAPTCHA is shown (default: 3)
        'failed_attempts' => 3,

        // Time in minutes after which new login attempt will be allowed (default: 30)
        'expire_time' => 30,

        // Use HTTPS for reCAPTCHA (default: false)
        // Possible values: true, false
        'recaptcha_https' => false
    ),

    // select pagesize plugin (default: yes)
    // Possible values: yes, no
    'select_pagesize_plugin' => 'yes',

    // select pagesize plugin (default: yes)
    // Possible values: yes, no
    'show_folder_size_plugin' => 'yes',

    // TLS Icon plugin (default: yes)
    // Possible values: yes, no
    'tls_icon_plugin' => 'yes',

    // vcard_attach plugin (default: yes)
    // Possible values: yes, no
    'vcard_attach_plugin' => 'yes',

    // vcard_attachments plugin (default: yes)
    // Possible values: yes, no
    'vcard_attachments_plugin' => 'yes',

    // zipdownload plugin (default: yes)
    // Possible values: yes, no
    'zipdownload_plugin' => 'yes'
);
