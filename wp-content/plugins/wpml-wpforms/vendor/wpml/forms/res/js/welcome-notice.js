/* global ajaxurl */

jQuery(function ($) {
    "use strict";

    $(document).on('click', '#wpml-forms-welcome-notice .notice-dismiss', function () {
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            dataType: 'json',
            data: {'action': wpml_forms_welcome_notice.action, '_ajax_nonce': wpml_forms_welcome_notice._ajax_nonce}
        });
    });
});
