=head1 NAME

 Plugin::XtraRoundcubePlugins

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Plugin::XtraRoundcubePlugins;

use strict;
use warnings;
use autouse 'iMSCP::Debug' => qw/ debug error /;
use autouse 'iMSCP::Execute' => qw/ execute escapeShell /;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use autouse 'iMSCP::TemplateParser' => qw/ process replaceBloc /;
use Class::Autouse qw/ :nostat iMSCP::Database iMSCP::Dir iMSCP::File Servers::po iMSCP::Service Servers::cron /;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP XtraRoundcubePlugins plugin.

=head1 PUBLIC METHODS

=over 4

=item install( )

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->_checkRequirements();
    $rs ||= $self->_installPlugins();
    return $rs if $rs;
}

=item uninstall( )

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    $_[0]->_uninstallPlugins();
}

=item update( $fromVersion )

 Perform update tasks

 Param string $fromVersion Version from which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my ( $self, $fromVersion ) = @_;

    if ( version->parse( $fromVersion ) < version->parse( '2.0.0' ) ) {
        # Remove pop3fetcher plugin which is no longer provided
        my $rs = iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins/pop3fetcher" )->remove();
        $rs ||= Servers::cron->factory()->deleteTask( { TASKID => 'Plugin::XtraRoundcubePlugins::pop3fetcher' } );
        return $rs if $rs;
    }

    $self->install();
}

=item change( )

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
    $_[0]->install();
}

=item enable( )

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my $self = shift;

    my $rs = $self->_checkRequirements();
    $rs ||= $self->_setXtraRoundcubePlugin( 'enable' );
    return $rs if $rs;

    $self->_scheduleDovecotRestart() if $main::imscpConfig{'PO_SERVER'} eq 'dovecot';

    unless ( defined $main::execmode && $main::execmode eq 'setup' ) {
        local $@;
        eval { iMSCP::Service->getInstance()->reload( 'imscp_panel' ); };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
    }

    0;
}

=item disable( )

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my $self = shift;

    my $rs = $self->_setXtraRoundcubePlugin( 'disable' );
    return $rs if $rs;

    $self->_scheduleDovecotRestart() if $main::imscpConfig{'PO_SERVER'} eq 'dovecot';

    unless ( defined $main::execmode && $main::execmode eq 'setup' ) {
        local $@;
        eval { iMSCP::Service->getInstance()->reload( 'imscp_panel' ); };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize plugin

 Return Plugin::XtraRoundcubePlugins or die on failure

=cut

sub _init
{
    my $self = shift;

    # Force return value from plugin module
    $self->{'FORCE_RETVAL'} = 'yes';
    $self;
}

=item _installPlugins( )

 Install plugins

 Return int 0 on success, other on failure

=cut

sub _installPlugins
{
    my $self = shift;

    my $roundcubePlugin = "$main::imscpConfig{'PLUGINS_DIR'}/XtraRoundcubePlugins/roundcube-plugins";
    my $configPlugin = "$main::imscpConfig{'PLUGINS_DIR'}/XtraRoundcubePlugins/config-templates";
    my $pluginFolder = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

    my $rs = execute( "cp -fR $roundcubePlugin/* $pluginFolder/", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    return $rs if $rs;

    $rs = execute( "cp -fR $configPlugin/* $pluginFolder/", \$stdout, \$stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;

    $rs ||= $self->_installComposerPackages();
    return $rs if $rs;

    my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $panelGName = $panelUName;

    $rs = setRights( $pluginFolder, { user => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0440', recursive => 1 } );

    for ( qw/ additional_message_headers authres_status calendar carddav emoticons enigma folder_info managesieve newmail_notifier nextcloud password quota rcguard show_folder_size zipdownload / ) {
        $rs ||= $self->_configurePlugin( $_, 'config.inc.php' );
        return $rs if $rs;
    }

    if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
        $rs = $self->_configurePlugin( 'managesieve', 'config.inc.php' );
        return $rs if $rs;
    }

    0;
}

=item _uninstallPlugins( )

 Uninstall plugins

 Return int 0 on success, other on failure

=cut

sub _uninstallPlugins
{
    my $self = shift;

    my $pluginSrcDir = "$main::imscpConfig{'PLUGINS_DIR'}/XtraRoundcubePlugins/roundcube-plugins";
    my $pluginDestDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

    for ( iMSCP::Dir->new( dirname => $pluginSrcDir )->getDirs() ) {
        my $rs = iMSCP::Dir->new( dirname => "$pluginDestDir/$_" )->remove();
        return $rs if $rs;
    }

    my $rs = $self->_deconfigurePlugin( 'managesieve', 'config.inc.php' );
    $rs ||= $self->_deconfigurePlugin( 'managesieve', 'imscp_default.sieve' );
    $rs ||= $self->_deconfigurePlugin( 'newmail_notifier', 'config.inc.php' );
}

=item _setXtraRoundcubePlugin( $action )

 Activate or deactivate the plugins

 Param string $action Action to be performed (enable|disable)
 Return int 0 on success, other on failure

=cut

sub _setXtraRoundcubePlugin
{
    my ( $self, $action ) = @_;

    my $pluginConffile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php";
    my $file = iMSCP::File->new( filename => $pluginConffile );
    my $fileContent = $file->get();
    unless ( defined $fileContent ) {
        error( sprintf( "Couldn't read %s", $pluginConffile ));
        return 1;
    }

    $fileContent = replaceBloc( "\n# Begin Plugin::XtraRoundcubePlugins\n", "Ending Plugin::XtraRoundcubePlugins\n", '', $fileContent );

    if ( $action eq 'enable' ) {
        my @plugins = ();

        for ( qw/ acl account_details additional_imap additional_smtp advanced_search contextmenu contextmenu_folder easy_unsubscribe fail2ban help message_highlight odfviewer pdfviewer select_pagesize show_folder_size tls_icon vcard_attach vcard_attachments /
        ) {
            next unless lc( $self->{'config'}->{$_ . '_plugin'} ) eq 'yes';
            push @plugins, $_;
        }

        if ( lc( $self->{'config'}->{'additional_message_headers_plugin'} ) eq 'yes' ) {
            push @plugins, 'additional_message_headers';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'additional_message_headers', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'additional_message_headers', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'archive_plugin'} ) eq 'yes' ) {
            push @plugins, 'archive';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'archive', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'archive', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'authres_status_plugin'} ) eq 'yes' ) {
            push @plugins, 'authres_status';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'authres_status', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'authres_status', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'calendar_plugin'} ) eq 'yes' ) {
            push @plugins, 'calendar';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'calendar', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'calendar', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'carddav_plugin'} ) eq 'yes' ) {
            push @plugins, 'carddav';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'carddav', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'carddav', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'enigma_plugin'} ) eq 'yes' ) {
            push @plugins, 'enigma';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'enigma', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'enigma', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'emoticons_plugin'} ) eq 'yes' ) {
            push @plugins, 'emoticons';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'emoticons', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'emoticons', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'folder_info_plugin'} ) eq 'yes' ) {
            push @plugins, 'folder_info';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'folder_info', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'folder_info', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'help_plugin'} ) eq 'yes' ) {
            push @plugins, 'help';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'help', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'help', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'keyboard_shortcuts_plugin'} ) eq 'yes' ) {
            push @plugins, 'keyboard_shortcuts';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'keyboard_shortcuts', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'keyboard_shortcuts', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'nextcloud_plugin'} ) eq 'yes' ) {
            push @plugins, 'nextcloud';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'nextcloud', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'nextcloud', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'persistent_login_plugin'} ) eq 'yes' ) {
            push @plugins, 'persistent_login';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'persistent_login', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'persistent_login', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'quota_plugin'} ) eq 'yes' ) {
            push @plugins, 'quota';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'quota', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'quota', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'managesieve_plugin'} ) eq 'yes' && $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_checkManagesieveRequirements();
            return $rs if $rs;
            push @plugins, 'managesieve';
            $rs = $self->_modifyDovecotConfig( 'managesieve', 'add' );
            return $rs if $rs;
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'managesieve', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'new_user_dialog_plugin'} ) eq 'yes' ) {
            push @plugins, 'new_user_dialog';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'new_user_dialog', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'new_user_dialog', 'remove' );
            return $rs if $rs;
        }

        if ( lc( $self->{'config'}->{'password_plugin'} ) eq 'yes' ) {
            push @plugins, 'password';
            if ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
                my $rs = $self->_modifyDovecotConfig( 'password', 'add' );
                return $rs if $rs;
            }
        } elsif ( $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
            my $rs = $self->_modifyDovecotConfig( 'password', 'remove' );
            return $rs if $rs;
        }

        my $roundcubePluginConfig = "\n# Begin Plugin::XtraRoundcubePlugins\n";
        $roundcubePluginConfig .= '$config[\'plugins\'] = array_merge($config[\'plugins\'], array(' .
            "\n\t" . ( join ', ', map { qq/'$_'/ } @plugins ) . "\n));\n";
        $roundcubePluginConfig .= "\n \n" . '$config[\'pagesize_options\'] = [10, 15, 20, 25, 30, 40, 50];' . "\n \n";
        $roundcubePluginConfig .= "# Ending Plugin::XtraRoundcubePlugins\n";
        $fileContent .= $roundcubePluginConfig;
    } elsif ( $action eq 'disable' && $main::imscpConfig{'PO_SERVER'} eq 'dovecot' ) {
        my $rs = $self->_modifyDovecotConfig( 'archive', 'remove' );
        $rs ||= $self->_modifyDovecotConfig( 'managesieve', 'remove' );
        return $rs if $rs;
    }

    my $rs = $file->set( $fileContent );
    $rs ||= $file->save();
}

=item _configurePlugin( $pluginName, $pluginConffileTpl )

 Configure the given plugin

 Param string pluginName Plugin name
 Param string $pluginConffileTpl Plugin configuration file template
 Return int 0 on success, other on failure

=cut

sub _configurePlugin
{
    my ( $self, $pluginName, $pluginConffileTpl ) = @_;

    my $pluginFolder = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";
    my $file = iMSCP::File->new( filename => "$pluginFolder/$pluginName/$pluginConffileTpl" );
    my $fileContent = $file->get();
    unless ( defined $fileContent ) {
        error( sprintf( "Couldn't read %s", $file->{'filename'} ));
        return 1;
    }

    my $data = {};
    if ( $pluginName eq 'authres_status' ) {
        $data = {
            enable_authres_status_column => $self->{'config'}->{'authres_status_config'}->{'enable_authres_status_column'}
        };
    } elsif ( $pluginName eq 'calendar' ) {
        $data = {
            calendar_driver         => $self->{'config'}->{'calendar_config'}->{'calendar_driver'},
            calendar_driver_default => $self->{'config'}->{'calendar_config'}->{'calendar_driver_default'},
            caldav_url              => $self->{'config'}->{'calendar_config'}->{'caldav_url'},
            calendar_default_view   => $self->{'config'}->{'calendar_config'}->{'calendar_default_view'},
            calendar_sync_period    => $self->{'config'}->{'calendar_config'}->{'calendar_sync_period'},
            calendar_crypt_key      => $self->{'config'}->{'calendar_config'}->{'calendar_crypt_key'},
            calendar_first_day      => $self->{'config'}->{'calendar_config'}->{'calendar_first_day'},
            calendar_caldav_debug   => $self->{'config'}->{'calendar_config'}->{'calendar_caldav_debug'} ? 'true' : 'false',
            calendar_ical_debug     => $self->{'config'}->{'calendar_config'}->{'calendar_ical_debug'} ? 'true' : 'false'
        };
    } elsif ( $pluginName eq 'carddav' ) {
        $data = {
            carddav_name         => $self->{'config'}->{'carddav_config'}->{'carddav_name'},
            carddav_url          => $self->{'config'}->{'carddav_config'}->{'carddav_url'},
            carddav_active       => $self->{'config'}->{'carddav_config'}->{'carddav_active'},
            carddav_readonly     => $self->{'config'}->{'carddav_config'}->{'carddav_readonly'},
            carddav_refresh_time => $self->{'config'}->{'carddav_config'}->{'carddav_refresh_time'}
        };
    } elsif ( $pluginName eq 'enigma' ) {
        $data = {
            enigma_pgp_homedir => $self->{'config'}->{'enigma_config'}->{'enigma_pgp_homedir'}
        };
    } elsif ( $pluginName eq 'help' ) {
        $data = {
            help_source => $self->{'config'}->{'help_config'}->{'help_source'}
        };
    } elsif ( $pluginName eq 'managesieve' ) {
        $data = {
            managesieve_default     => "$pluginFolder/$pluginName/imscp_default.sieve",
            managesieve_vacation    => $self->{'config'}->{'managesieve_config'}->{'managesieve_vacation'},
            managesieve_forward     => $self->{'config'}->{'managesieve_config'}->{'managesieve_forward'},
            managesieve_script_name => $self->{'config'}->{'managesieve_config'}->{'managesieve_script_name'}
        };
    } elsif ( $pluginName eq 'newmail_notifier' ) {
        $data = {
            newmail_notifier_basic   => $self->{'config'}->{'newmail_notifier_config'}->{'newmail_notifier_basic'} ? 'true' : 'false',
            newmail_notifier_sound   => $self->{'config'}->{'newmail_notifier_config'}->{'newmail_notifier_sound'} ? 'true' : 'false',
            newmail_notifier_desktop => $self->{'config'}->{'newmail_notifier_config'}->{'newmail_notifier_desktop'} ? 'true' : 'false'
        };
	} elsif ( $pluginName eq 'nextcloud' ) {
        $data = {
            nextcloud_url               => $self->{'config'}->{'nextcloud_config'}->{'nextcloud_url'},
            roundcube_nextcloud_des_key => $self->{'config'}->{'nextcloud_config'}->{'roundcube_nextcloud_des_key'}
        };
	} elsif ( $pluginName eq 'quota' ) {
        $data = {
            quota_config       => $self->{'config'}->{'quota_config'}->{'quota_config'},
            show_admin_contact => $self->{'config'}->{'quota_config'}->{'show_admin_contact'} ? 'true' : 'false'
        };
    } elsif ( $pluginName eq 'password' ) {
        my ( $imscpVersion ) = $main::imscpConfig{'Version'} =~ /^(?:git\s+)?(\d+.\d+)/i;
        unless ( defined $imscpVersion ) {
            error( "Couldn't not determine i-MSCP version in use" );
            return 1;
        }

        my ( $passwordScheme, $passwordShemeSqlMacro );
        if ( version->parse( $imscpVersion ) >= version->parse( '1.4' ) ) {
            $passwordScheme = 'sha512-crypt';
            $passwordShemeSqlMacro = '%P';
        } else {
            $passwordScheme = 'clear';
            $passwordShemeSqlMacro = '%p';
        }

        tie %{ $self->{'ROUNDCUBE'} }, 'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/roundcube/roundcube.data";

        ( my $dbUser = $self->{'ROUNDCUBE'}->{'DATABASE_USER'} ) =~ s%(')%\\$1%g;
        ( my $dbPass = $self->{'ROUNDCUBE'}->{'DATABASE_PASSWORD'} ) =~ s%(')%\\$1%g;

        $data = {
            password_confirm_current  => $self->{'config'}->{'password_config'}->{'password_confirm_current'} ? 'true' : 'false',
            password_minimum_length   => $self->{'config'}->{'password_config'}->{'password_minimum_length'},
            password_require_nonalpha => $self->{'config'}->{'password_config'}->{'password_require_nonalpha'} ? 'true' : 'false',
            password_force_new_user   => $self->{'config'}->{'password_config'}->{'password_force_new_user'} ? 'true' : 'false',
            DB_NAME                   => $main::imscpConfig{'DATABASE_NAME'},
            DB_HOST                   => $main::imscpConfig{'DATABASE_HOST'},
            DB_PORT                   => $main::imscpConfig{'DATABASE_PORT'},
            DB_USER                   => $dbUser,
            DB_PASS                   => $dbPass,
            PASSWORD_SCHEME           => $passwordScheme,
            PASSWORD_SCHEME_SQL_MACRO => $passwordShemeSqlMacro,
        };
    } elsif ( $pluginName eq 'rcguard' ) {
        $data = {
            recaptcha_publickey  => $self->{'config'}->{'rcguard_config'}->{'recaptcha_publickey'},
            recaptcha_privatekey => $self->{'config'}->{'rcguard_config'}->{'recaptcha_privatekey'},
            failed_attempts      => $self->{'config'}->{'rcguard_config'}->{'failed_attempts'},
            expire_time          => $self->{'config'}->{'rcguard_config'}->{'expire_time'},
            recaptcha_https      => $self->{'config'}->{'rcguard_config'}->{'recaptcha_https'} ? 'true' : 'false'
        };
    }

    my $rs = $file->set( process( $data, $fileContent ));
    $rs ||= $file->save();
}

=item _deconfigurePlugin( $pluginName, $pluginConffileTpl )

 Deconfigure the given plugin

 Param string pluginName Plugin name
 Param string $pluginConffileTpl Plugin configuration file template
 Return int 0 on success, other on failure

=cut

sub _deconfigurePlugin
{
    my ( undef, $pluginName, $pluginConffileTpl ) = @_;

    my $filePath = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins/$pluginName/$pluginConffileTpl";
    return 0 unless -f $filePath;

    iMSCP::File->new( filename => $filePath )->delFile();
}

=item _checkManagesieveRequirements( )

 Check the managesieve requirements

 Return int 0 if all requirement are met, other otherwise

=cut

sub _checkManagesieveRequirements
{
    my $ret = 0;

    for ( qw/ dovecot-sieve dovecot-managesieved / ) {
        if ( execute( "dpkg-query -W  -f='\${Status}' $_ 2>/dev/null | grep -q '\\sinstalled\$'" ) ) {
            error( sprintf( 'The `%s` package is not installed on your system', $_ ));
            $ret ||= 1;
        }
    }

    $ret;
}

=item _modifyDovecotConfig( $plugin, $action )

 Modify dovecot config file dovecot.conf

 Return int 0 on success, other on failure

=cut

sub _modifyDovecotConfig
{
    my ( undef, $plugin, $action ) = @_;

    # Get the Dovecot config file
    my $dovecotConfig = '/etc/dovecot/dovecot.conf';

    my $file = iMSCP::File->new( filename => $dovecotConfig );
    my $fileContent = $file->get();
    unless ( defined $fileContent ) {
        error( sprintf( "Couldn't read %s file", $dovecotConfig ));
        return 1;
    }

    # check the Dovecot version
    my $rs = execute( '/usr/sbin/dovecot --version', \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr;
    error( "Couldnt't get Dovecot version. Is Dovecot installed?" ) if $rs && !$stderr;
    return $rs if $rs;

    chomp( $stdout );
    $stdout =~ m/^([0-9\.]+)\s*/;
    my $version = $1;

    unless ( $version ) {
        error( 'Could not find Dovecot version' );
        return 1;
    }

    if ( $plugin eq 'archive' ) {
        if ( version->parse( $version ) > version->parse( '2.1.0' ) ) {
            $fileContent =~ s/\n\t# Begin Plugin::XtraRoundcubePlugin::archive.*Ending Plugin::XtraRoundcubePlugin::archive\n//sm;
            if ( $action eq 'add' ) {
                $fileContent =~ s/^(namespace\s+inbox\s+\{.*?)(^\})/$1\n\t# Begin Plugin::XtraRoundcubePlugin::archive\n\tmailbox Archive \{\n\t\tauto = subscribe\n\t\tspecial_use = \\Archive\n\t\}\n\t# Ending Plugin::XtraRoundcubePlugin::archive\n$2/sm;
            }
        } else {
            $fileContent =~ s/^\t# Begin Plugin::XtraRoundcubePlugin::archive::1st.*Ending Plugin::XtraRoundcubePlugin::archive::1st\n//sm;
            if ( $action eq 'add' ) {
                $fileContent =~ s/^(plugin\s+\{)/$1\n\t# Begin Plugin::XtraRoundcubePlugin::archive::1st\n\tautocreate = INBOX.Archive\n\tautosubscribe = INBOX.Archive\n\t# Ending Plugin::XtraRoundcubePlugin::archive::1st/sm;
            }
        }
    } elsif ( $plugin eq 'managesieve' ) {
        if ( $action eq 'add' ) {
            $fileContent =~ s/^\t# Begin Plugin::XtraRoundcubePlugin::managesieve::1st.*Ending Plugin::XtraRoundcubePlugin::managesieve::1st\n//sgm;
            $fileContent =~ s/^(plugin\s+\{)/$1\n\t# Begin Plugin::XtraRoundcubePlugin::managesieve::1st\n\tsieve = ~\/dovecot.sieve\n\t# Ending Plugin::XtraRoundcubePlugin::managesieve::1st/sgm;

            $fileContent =~ s/^\t# Begin Plugin::XtraRoundcubePlugin::managesieve::2nd.*(\tmail_plugins\s+=.*?)\s+sieve\n\t# Ending Plugin::XtraRoundcubePlugin::managesieve::2nd\n/$1\n/sgm;
            $fileContent =~ s/^(protocol\s+lda.*?)(\tmail_plugins\s+=.*?)$/$1\t# Begin Plugin::XtraRoundcubePlugin::managesieve::2nd\n$2 sieve\n\t# Ending Plugin::XtraRoundcubePlugin::managesieve::2nd/sgm;

            if ( version->parse( $version ) < version->parse( '2.0.0' ) ) {
                $fileContent =~ s/^# Begin Plugin::XtraRoundcubePlugin::managesieve::3nd.*(protocols\s+=.*?)\s+managesieve.*Ending Plugin::XtraRoundcubePlugin::managesieve::3nd\n/$1\n/sgm;
                $fileContent =~ s/^(protocols\s+=.*?)$/# Begin Plugin::XtraRoundcubePlugin::managesieve::3nd\n$1 managesieve\n\nprotocol managesieve {\n\tlisten = localhost:4190\n}\n# Ending Plugin::XtraRoundcubePlugin::managesieve::3nd/sgm;
            }
        } elsif ( $action eq 'remove' ) {
            $fileContent =~ s/^\t# Begin Plugin::XtraRoundcubePlugin::managesieve::1st.*Ending Plugin::XtraRoundcubePlugin::managesieve::1st\n//sgm;
            $fileContent =~ s/^\t# Begin Plugin::XtraRoundcubePlugin::managesieve::2nd.*(\tmail_plugins\s*=.*?)\s+sieve\n\t# Ending Plugin::XtraRoundcubePlugin::managesieve::2nd\n/$1\n/sgm;

            if ( version->parse( $version ) < version->parse( '2.0.0' ) ) {
                $fileContent =~ s/^# Begin Plugin::XtraRoundcubePlugin::managesieve::3nd.*(protocols\s+=.*?)\s+managesieve.*Ending Plugin::XtraRoundcubePlugin::managesieve::3nd\n/$1\n/sgm;
            }
        }
    }

    $rs = $file->set( $fileContent );
    $rs ||= $file->save();
}

=item _scheduleDovecotRestart( )

 Schedule Dovecot restart

 Return int 0 on success, other on failure

=cut

sub _scheduleDovecotRestart
{
    Servers::po->factory()->{'restart'} = 'yes';
    0;
}

=item _checkRequirements( )

 Check for requirements

 Return int 0 if all requirements are met, other otherwise

=cut

sub _checkRequirements
{
    my $self = shift;

    unless ( grep ($_ eq 'Roundcube', split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'}) ) {
        error( 'Roundcube is not installed. You must install Install it by running the imscp-reconfigure script.' );
        return 1;
    }

    tie %{ $self->{'ROUNDCUBE'} }, 'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/roundcube/roundcube.data";

    my $version = $self->{'ROUNDCUBE'}->{'ROUNDCUBE_VERSION'};

    if ( version->parse( $version ) < version->parse( '1.4.6' ) ) {
        error( sprintf( 'Your Roundcube version (%s) is not compatible with this plugin.', $version ));
        return 1;
    }

    0;
}

=item _installComposerPackages( )

 Install required composer package for calendaring library

 Return int 0 on success, other on failure

=cut

sub _installComposerPackages
{
    my $webmailDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";

    return 0 unless -f "$webmailDir/composer.json" || -f "$webmailDir/composer.json-dist";

    if ( -f "$webmailDir/composer.json-dist" ) {
        my $rs = iMSCP::File->new( filename => "$webmailDir/composer.json-dist" )->moveFile( "$webmailDir/composer.json" );
        return $rs if $rs;
    }

    my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $panelGName = $panelUName;

    # Make sure that Web user can write into base directory
    my $rs = setRights( $webmailDir, { user => $panelUName, group => $panelGName, mode => '0750' } );
    return $rs if $rs;

    # Make sure that composer.json and composer.lock (if any) files are writable and owned by expected Web user
    for ( 'composer.json', 'composer.lock' ) {
        next unless -f "$webmailDir/$_";
        $rs = setRights( "$webmailDir/$_", { user => $panelUName, group => $panelGName, mode => '0600' } );
        return $rs if $rs;
    };

    # Make sure that composer vendor directory is writable and owned by expected Web user
    $rs = setRights( "$webmailDir/vendor", { user => $panelUName, group => $panelGName, dirmode => '0700', filemode => '0600', recursive => 1 } );
    return $rs if $rs;

    unless ( -f "/var/local/imscp/composer.phar" ) {
        error( "Couldn't find composer.phar file in /var/local/imscp/ directory" );
        return 1;
    }

    my $imscpWebUser = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    $rs = execute(
        sprintf( "su -l $imscpWebUser -s /bin/sh -c %s", escapeShell(
            "COMPOSER_HOME=$main::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer " # Override composer homedir
                . 'COMPOSER_PROCESS_TIMEOUT=2000 '                                        # Increase composer process timeout for slow connections
                . 'COMPOSER_NO_INTERACTION=1 '                                            # not user interaction
                . 'COMPOSER_DISCARD_CHANGES=true '                                        # discard any change made in vendor
                . "php -d date.timezone=$main::imscpConfig{'TIMEZONE'} -d allow_url_fopen=1 "
                . '-d suhosin.executor.include.whitelist=phar '
                . "/var/local/imscp/composer.phar require  --no-ansi --no-interaction --working-dir=$webmailDir --update-no-dev "
                . '--ignore-platform-reqs --prefer-stable --no-suggest sabre/vobject ~3.3.3'
        )),
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    debug( $stderr ) unless $rs || !$stderr;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
