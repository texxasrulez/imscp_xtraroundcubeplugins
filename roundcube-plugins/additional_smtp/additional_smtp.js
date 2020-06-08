/***************************************************************************
 * Roundcube "additional_smtp" plugin.
 ***************************************************************************/

$(document).ready(function() {
    document.getElementById("rcmfd_additional_smtp.smtpserver") && document.getElementById("rcmfd_additional_smtp.smtpserver").readOnly && $(document.getElementById("rcmfd_additional_smtp.smtpserver")).parent().parent().hide()
});