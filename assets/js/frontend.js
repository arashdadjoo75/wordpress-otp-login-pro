/**
 * OTP Login Pro - Frontend JavaScript
 * Handles all frontend interactions for OTP authentication
 */

(function($) {
    'use strict';
    
    const OTPLogin = {
        
        init() {
            this.bindEvents();
            this.setupOTPInputs();
            this.initAutoFill();
        },
        
        bindEvents() {
            // Send OTP
            $(document).on('click', '#otp-send-btn', this.sendOTP.bind(this));
            
            // Verify OTP
            $(document).on('click', '#otp-verify-btn', this.verifyOTP.bind(this));
            
            // Resend OTP
            $(document).on('click', '#otp-resend-btn', this.resendOTP.bind(this));
            
            // Back button
            $(document).on('click', '#otp-back-btn', this.goBack.bind(this));
            
            // Enter key handling
            $(document).on('keypress', '#otp-identifier', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#otp-send-btn').click();
                }
            });
        },
        
        setupOTPInputs() {
            const inputs = document.querySelectorAll('.otp-digit');
            
            inputs.forEach((input, index) => {
                // Auto-focus next input
                input.addEventListener('input', (e) => {
                    const value = e.target.value;
                    
                    if (value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                    
                    this.updateOTPCode();
                });
                
                // Handle backspace
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
                
                // Paste handler
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasteData = e.clipboardData.getData('text').slice(0, inputs.length);
                    
                    pasteData.split('').forEach((char, i) => {
                        if (inputs[i]) {
                            inputs[i].value = char;
                        }
                    });
                    
                    this.updateOTPCode();
                });
            });
        },
        
        updateOTPCode() {
            const inputs = document.querySelectorAll('.otp-digit');
            const code = Array.from(inputs).map(input => input.value).join('');
            document.getElementById('otp-code').value = code;
            
            // Auto-verify when all digits entered
            if (code.length === inputs.length) {
                $('#otp-verify-btn').prop('disabled', false).addClass('pulse');
            }
        },
        
        initAutoFill() {
            // Web OTP API support
            if ('OTPCredential' in window) {
                navigator.credentials.get({
                    otp: { transport:['sms'] }
                }).then(otp => {
                    if (otp && otp.code) {
                        this.fillOTPCode(otp.code);
                    }
                }).catch(err => {
                    console.log('OTP autofill error:', err);
                });
            }
        },
        
        fillOTPCode(code) {
            const inputs = document.querySelectorAll('.otp-digit');
            code.split('').forEach((digit, index) => {
                if (inputs[index]) {
                    inputs[index].value = digit;
                }
            });
            this.updateOTPCode();
        },
        
        async sendOTP(e) {
            e.preventDefault();
            
            const btn = $(e.currentTarget);
            const identifier = $('#otp-identifier').val().trim();
            
            if (!identifier) {
                this.showMessage('Please enter your phone number or email', 'error');
                return;
            }
            
            btn.prop('disabled', true).text(otpLoginPro.i18n.sending);
            this.clearMessages();
            
            try {
                const response = await $.ajax({
                    url: otpLoginPro.ajax_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'otp_pro_send',
                        nonce: otpLoginPro.nonce,
                        identifier,
                        method: 'auto'
                    }
                });
                
                if (response.success) {
                    this.showMessage(response.data.message, 'success');
                    this.showStep2(identifier);
                    this.startTimer(response.data.expires_in || 300);
                } else {
                    this.showMessage(response.data.message || otpLoginPro.i18n.error, 'error');
                }
            } catch (error) {
                this.showMessage('Network error. Please try again', 'error');
            } finally {
                btn.prop('disabled', false).text('Send OTP Code');
            }
        },
        
        async verifyOTP(e) {
            e.preventDefault();
            
            const btn = $(e.currentTarget);
            const identifier = $('#otp-identifier').val();
            const otp = $('#otp-code').val();
            const remember = $('#otp-remember').is(':checked');
            
            if (otp.length < 6) {
                this.showMessage('Please enter the complete verification code', 'error');
                return;
            }
            
            btn.prop('disabled', true).text(otpLoginPro.i18n.verifying);
            this.clearMessages();
            
            try {
                const response = await $.ajax({
                    url: otpLoginPro.ajax_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'otp_pro_verify',
                        nonce: otpLoginPro.nonce,
                        identifier,
                        otp,
                        remember
                    }
                });
                
                if (response.success) {
                    this.showMessage(response.data.message, 'success');
                    
                    // Play success sound if enabled
                    if (otpLoginPro.settings.sound_enabled) {
                        this.playSuccessSound();
                    }
                    
                    // Redirect
                    setTimeout(() => {
                        const redirect = $('.otp-login-form-container').data('redirect') || response.data.redirect;
                        window.location.href = redirect;
                    }, 1000);
                } else {
                    this.showMessage(response.data.message || 'Invalid verification code', 'error');
                    this.shakeForm();
                }
            } catch (error) {
                this.showMessage('Network error. Please try again', 'error');
            } finally {
                btn.prop('disabled', false).text('Verify & Login');
            }
        },
        
        async resendOTP(e) {
            e.preventDefault();
            
            const btn = $(e.currentTarget);
            const identifier = $('#otp-identifier').val();
            
            btn.prop('disabled', true).text('Sending...');
            
            try {
                const response = await $.ajax({
                    url: otpLoginPro.ajax_url,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'otp_pro_resend',
                        nonce: otpLoginPro.nonce,
                        identifier
                    }
                });
                
                if (response.success) {
                    this.showMessage('New code sent!', 'success');
                    this.startTimer(response.data.expires_in || 300);
                    
                    // Clear OTP inputs
                    document.querySelectorAll('.otp-digit').forEach(input => input.value = '');
                } else {
                    this.showMessage(response.data.message, 'error');
                }
            } catch (error) {
                this.showMessage('Error resending code', 'error');
            } finally {
                btn.prop('disabled', true).text('Code Sent');
            }
        },
        
        goBack(e) {
            e.preventDefault();
            this.showStep1();
        },
        
        showStep1() {
            $('.otp-step-1').show().addClass('active');
            $('.otp-step-2').hide().removeClass('active');
            this.clearMessages();
        },
        
        showStep2(identifier) {
            $('.otp-step-1').hide().removeClass('active');
            $('.otp-step-2').show().addClass('active');
            $('.otp-identifier-display').text(identifier);
            
            // Focus first OTP input
            $('.otp-digit').first().focus();
        },
        
        startTimer(seconds) {
            const timerEl = $('#otp-timer');
            const resendBtn = $('#otp-resend-btn');
            
            let remaining = seconds;
            resendBtn.prop('disabled', true);
            
            const interval = setInterval(() => {
                const minutes = Math.floor(remaining / 60);
                const secs = remaining % 60;
                
                timerEl.text(`Code expires in ${minutes}:${secs.toString().padStart(2, '0')}`);
                
                // Enable resend after cooldown
                const cooldown = otpLoginPro.settings.cooldown || 60;
                if (remaining <= (seconds - cooldown)) {
                    resendBtn.prop('disabled', false);
                }
                
                remaining--;
                
                if (remaining < 0) {
                    clearInterval(interval);
                    timerEl.text('Code expired').css('color', 'red');
                    resendBtn.prop('disabled', false).text('Resend Code');
                }
            }, 1000);
        },
        
        showMessage(message, type = 'info') {
            const messagesContainer = $('.otp-messages');
            const messageEl = $('<div>')
                .addClass(`otp-message otp-message-${type}`)
                .html(`<span class="otp-message-icon"></span><span class="otp-message-text">${message}</span>`)
                .hide()
                .fadeIn(300);
            
            messagesContainer.html(messageEl);
            
            if (type === 'success') {
                setTimeout(() => messageEl.fadeOut(300), 5000);
            }
        },
        
        clearMessages() {
            $('.otp-messages').empty();
        },
        
        shakeForm() {
            $('.otp-step-2').addClass('shake');
            setTimeout(() => $('.otp-step-2').removeClass('shake'), 500);
        },
        
        playSuccessSound() {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZi0ALFmS669+WRggXaLvt559NEAxPqOPwtmMcBjiP1vLMeS0GJHbH8N2RQAoUXrTp66hVFApGnuDyvmwhBSuAzvLZi0AJFmS666CWRQ';
            audio.play().catch(() => {});
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(() => {
        OTPLogin.init();
    });
    
    // Export for external use
    window.OTPLoginPro = OTPLogin;
    
})(jQuery);
