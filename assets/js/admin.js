// Admin JavaScript
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Test provider
        $('.test-provider').on('click', function (e) {
            e.preventDefault();
            const provider = $(this).data('provider');
            const testNumber = prompt('Enter test phone number/email:');

            if (!testNumber) return;

            $.post(ajaxurl, {
                action: 'otp_pro_test_provider',
                nonce: otpLoginProAdmin.nonce,
                provider_id: provider,
                test_number: testNumber
            }, function (response) {
                if (response.success) {
                    alert('Test successful! OTP sent.');
                } else {
                    alert('Test failed: ' + response.data.message);
                }
            });
        });
    });

})(jQuery);
