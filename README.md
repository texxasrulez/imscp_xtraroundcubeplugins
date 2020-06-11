# i-MSCP XtraRoundcubePlugins plugin v1.0.0

**Forked by texxasrulez**  

I have been playing with i-MSCP long enough now that I know just enough to be dangerous and have forked the RoundcubePlugin the developers give away and updated for use with Roundcube version 1.4.x. I have added several popular plugins and enabled many stock plugins for a more enhanced Roundcube experience. I have tried to make as many customizable configurations as possible and will add more in the future as I knit pick this.  
This repo does have several of my own plugins added as well as many that I personally like. If you would like more to be added, just let me know and I will do my best to accommodate your request.  
Many users wanted a caldav capable calendar plugin, now there is one. You will need to have a caldav server already setup and know your URL structure to add that to your main config.php file to sync with your calendar server. I use this caldav_calendar extension exclusively with NextCloud and have no issues with i-MSCP because I have the "30_apache2_tools_proxy.pl" listener installed on my system which will take care of cross script domain issues as long as your NextCloud instance is installed as such "https://www.domain.td/cloud" Directory name not an issue as long as URL is as described. No problems on my end using this setup.  

I will add I have not quite figured out how to make the enigma plugin work "out of the box" using the system version of Roundcube. My instance I have uploaded and installed to my actual domain (separate install using customer ftp login to upload) works great by adding required open_basedir options using the "10_php_confoptions_override.pl" listener file provided by i-MSCP developers with required directories but seems to not work with the panel version of Roundcube. I have added the open_basedir in all php.ini's I can find and does not work. I will figure it out and let everyone know.  

I will have more detailed instructions soon. Here is what I have so far ...

***Roundcube 1.4.x and higher REQUIRED to work***

### Get Roundcube 1.4.x with extra skins  

Download my repo if you want additional skins/themes included, if not, get official Roundcube download here https://roundcube.net/download using the URL with the wget command below for seamless install/upgrade.  

Move to your working directory (I use this one)  

`cd /usr/local/src/`  

Download mine or official Roundcube (link above)  

`https://github.com/texxasrulez/roundcube_xtra'  
Extract file and then upload the tar.gz file located in the "tarball_installer" directory to your working directory of your server.   

Extract Files  

`tar -xzf roundcubemail-VERSION-complete_xtra.tar.gz`  

Install Roundcube to i-MSCP system

`cd /usr/local/src/roundcube_xtra-VERSION/bin'  

`./installto.sh /var/www/imscp/gui/public/tools/webmail/`  

Edit this file to allow install of my XtraRoundcubePlugins Plugin and change Rouncube version to match your install  

`nano /etc/imscp/roundcube/roundcube.data'  

Should install with no issues and execute the following commands for security  

`rm -r /var/www/imscp/gui/public/tools/webmail/installer'  

`rm -r /var/www/imscp/gui/public/tools/webmail/public_html` // This is not necessary but I do it  

Now you can upload and install my plugin XtraRoundcubePlugins here https://github.com/texxasrulez/imscp_xtraroundcubeplugins/ from the Plugin Manager Page in i-MSCP. Also, if you use the Spamassassin Plugin provided by the official i-MSCP team, you will need to download my version updated for Roundcube 1.4+ to work at https://github.com/texxasrulez/imscp_spamassassin for the updated version of Roundcube. Both are OSS as of this posting, so I have updated and kept original credits and requirements according to the GNU License.  

This repo will automatically add plugins to main config, with any other dependencies as well as inject proper schemas into database.  
You will only need to upload the `/tarball_installer/XtraRoundcubePlugins.tar.gz` file to i-MSCP's Plugin Manager Page, or the spamassassin plugin referenced above if using. Use the file located within "tarball_installer" as well to upload and install within i-MSCP Plugin Manager Page.  

**Be Advised**  
If you reconfigure i-MSCP, it will revert back to original Roundcube v 1.2+ and you will need to do this all over again. Good thing it is easy as pie, just backup your imscp_roundcube database before reconfiguring your system so your settings can be restored easily.  

## Requirements

- i-MSCP 1.3.x Serie (version >= 1.3.1), 1.4.x, 1.5.x Serie
- Dovecot (Only needed if you want use the managesieve Roundcube plugin)
- GNUPGP & open_basedir skills (Only if you need to use the enigma Roundcube plugin)
- NextCloud is required on same domain to use nextcloud plugin.
- Roundcube >= 1.4

### Debian / Ubuntu packages

The following package are required only if you want install the managesieve plugin for the Roundcube Webmail.

* dovecot-sieve
* dovecot-managesieved

You can install these packages by running the following commands:

```
# apt-get update
# apt-get install --no-install-recommends dovecot-sieve dovecot-managesieved
```

Prior executing the command above, be sure that you're using the i-MSCP Dovecot server implementation.

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface

Note that plugin installation can take up several minutes.

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed (compare it with the new version first)
5. Update the plugin list through the plugin management interface

Note that plugin update can take up several minutes.

## Plugin configuration

See [Configuration file](../XtraRoundcubePlugins/config.php)

When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin change by
triggering a plugin list update from the plugin management interface.

## Roundcube Webmail plugin list

### account_details plugin  

This plugin displays information about current user, mail stats and server info

### additional_imap plugin  

Add multiple IMAP accounts to Roundcube. GMail and Yahoo pre-configured

### additional_smtp plugin  

Send Messages through another account using SMTP

### advanced_search plugin  

Advanced search for Roundcube

### authres_status plugin  

Displays Authentication Status of email for DKIM

### additional_message_headers plugin

This plugin adds an header identifying the originating IP for the mails sent via the Roundcube Webmail.

### archive plugin

This plugin adds a button to the Roundcube Webmail toolbar which allows moving messages to an (user selectable) archive
folder. The button will appears after archive folder configuration.

### calendar plugin

This plugin provides calendar feature for Roundcube Webmail with caldav drivers added. A working caldav/carddav server required.  

### contextmenu plugin

This plugin adds contextual menu to the message list, folder list and address book. It allows to mark mails as
read/unread, delete, reply or forward them.

### contextmenu_folder plugin

This plugin adds contextual menu for folders in Roundcube.

### easy_unsubscribe plugin  

Just like it sound, easily unsubscribe from newsletters.

### fail2ban plugin  

Ban IP Addresses trying to guess their way into your system. Failed attempts are logged by IP and stored in database and iptables. IPs are released after a certain amount of time. Fail2ban needs to be installed.  

### folder_info plugin  

Adds a label to notify deletion of Junk and Trash Folders. This is good if you setup your system to delete old emails from Junk and Trash folders.  

### emoticons plugin

This plugin parses and display smileys and other emoticons found in body of mails.

### libcalendaring plugin  

Required for calendar

### libkolab plugin  

Required for calendar

### managesieve plugin

This plugin adds support for managesieve protocol and allows the users to manage their sieve mail rules.

Note that this plugins is disabled by default. You can enable it by updating the plugin configuration file.

A default Spam sieve rule will be created after the user opened the Filters configuration in Roundcube.

### newmail_notifier plugin

This plugin allows notifying user for new mails, by focusing browser window and changing favicon, playing a sound and
displaying a desktop notification.

### nextcloud plugin  

Adds tab to view NextCloud within Roundcube GUI. NextCloud must be installed and running on same domain.  

### odfviewer plugin

This plugin adds support for inline ODF file viewer.

### password plugin

This plugin allows to update email account password from the Roundcube Webmail.

### pdfviewer plugin

This plugin adds support for inline PDF file viewer.

### quota plugin  

Displays in a chart used and free space.

### rcguard plugin

This plugin logs the failed login attempts and requires users to go through a reCAPTCHA verification process when the
maximum number of login attempts has been reached. In other words, this plugin help mitigate dictionary attacks.

### select_pagesize plugin  

Plugin to choose how many emails to display per page. Additional line required to be placed in Roundcube's config.inc.php file is automatically added `$config['pagesize_options'] = [10, 15, 20, 25, 30, 40, 50];` during install.  

### show_folder_size plugin  

Adds information to show folder sizes

### tasklist plugin

This plugin add support for task management.

### tls_icon plugin  

Icon beside senders email address showing if sent with encryption

### vcard_attach plugin  

Sends a vCard as an attachement automatically to all outbound emails based on identity information or contact information.

### vcard_attachments plugin

Detect VCard attachments and show a button to add them to address book

### zipdownload plugin

This plugin adds an option to download all attachments of a message in one zip file.

**Enabled/Disabled by Default**  

I have disabled all the plugins that requires server admin skills to install and use  

~~~  
    'acl_plugin' => 'yes',
    'account_details_plugin' => 'yes',
    'additional_imap_plugin' => 'no',
    'additional_message_headers_plugin' => 'yes',
    'additional_smtp_plugin' => 'no',
    'advanced_search_plugin' => 'yes',
    'archive_plugin' => 'yes',
    'authres_status_plugin' => 'yes',
    'calendar_plugin' => 'no',
    'carddav_plugin' => 'no',
    'contextmenu_plugin' => 'yes',
    'contextmenu_folder_plugin' => 'yes',
    'easy_unsubscribe_plugin' => 'yes',
    'emoticons_plugin' => 'yes',
    'enigma_plugin' => 'no',
    'fail2ban_plugin' => 'no',
    'folder_info_plugin' => 'no',
    'message_highlight_plugin' => 'yes',
    'new_user_dialog_plugin' => 'yes',
    'nextcloud' => 'no',
    'odfviewer_plugin' => 'yes',
    'password_plugin' => 'yes',
    'pdfviewer_plugin' => 'yes',
    'persistent_login_plugin' => 'no',
    'quota' => 'no',
    'rcguard_plugin' => 'no',
    'select_pagesize_plugin' => 'yes',
    'show_folder_size_plugin' => 'yes',
    'tls_icon_plugin' => 'yes',
    'vcard_attach_plugin' => 'yes',
    'vcard_attachments_plugin' => 'yes',
    'zipdownload_plugin' => 'yes'
~~~  

## License

```
i-MSCP - internet Multi Server Control Panel
Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE)
