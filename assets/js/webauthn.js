/**
 * WebAuthn Client-Side Implementation
 */

(function ($) {
    'use strict';

    const OTPWebAuthn = {

        /**
         * Check if WebAuthn is supported
         */
        isSupported: function () {
            return window.PublicKeyCredential !== undefined &&
                navigator.credentials !== undefined;
        },

        /**
         * Register new credential
         */
        register: async function (userId) {
            if (!this.isSupported()) {
                alert('WebAuthn is not supported in your browser');
                return false;
            }

            try {
                // Get challenge from server
                const challengeResponse = await $.post(otpWebAuthn.ajaxUrl, {
                    action: 'webauthn_register',
                    nonce: otpWebAuthn.nonce
                });

                if (!challengeResponse.success) {
                    throw new Error(challengeResponse.data.message);
                }

                const publicKey = challengeResponse.data.publicKey;

                // Convert challenge to ArrayBuffer
                publicKey.challenge = this.base64ToArrayBuffer(publicKey.challenge);
                publicKey.user.id = this.base64ToArrayBuffer(publicKey.user.id);

                // Create credential
                const credential = await navigator.credentials.create({
                    publicKey: publicKey
                });

                // Send to server for verification
                const verifyResponse = await $.post(otpWebAuthn.ajaxUrl, {
                    action: 'webauthn_verify_registration',
                    nonce: otpWebAuthn.nonce,
                    credential: JSON.stringify({
                        id: credential.id,
                        rawId: this.arrayBufferToBase64(credential.rawId),
                        type: credential.type,
                        publicKey: this.arrayBufferToBase64(credential.response.getPublicKey())
                    })
                });

                if (verifyResponse.success) {
                    alert('Biometric authentication registered successfully!');
                    return true;
                } else {
                    throw new Error(verifyResponse.data.message);
                }

            } catch (error) {
                console.error('WebAuthn registration failed:', error);
                alert('Registration failed: ' + error.message);
                return false;
            }
        },

        /**
         * Authenticate with WebAuthn
         */
        authenticate: async function (username) {
            if (!this.isSupported()) {
                alert('WebAuthn is not supported in your browser');
                return false;
            }

            try {
                // Get challenge from server
                const challengeResponse = await $.post(otpWebAuthn.ajaxUrl, {
                    action: 'webauthn_authenticate',
                    nonce: otpWebAuthn.nonce,
                    username: username
                });

                if (!challengeResponse.success) {
                    throw new Error(challengeResponse.data.message);
                }

                const publicKey = challengeResponse.data.publicKey;

                // Convert challenge to ArrayBuffer
                publicKey.challenge = this.base64ToArrayBuffer(publicKey.challenge);

                publicKey.allowCredentials = publicKey.allowCredentials.map(cred => ({
                    ...cred,
                    id: this.base64ToArrayBuffer(cred.id)
                }));

                // Get assertion
                const assertion = await navigator.credentials.get({
                    publicKey: publicKey
                });

                // Verify assertion on server
                const verifyResponse = await $.post(otpWebAuthn.ajaxUrl, {
                    action: 'webauthn_verify_assertion',
                    nonce: otpWebAuthn.nonce,
                    assertion: JSON.stringify({
                        id: assertion.id,
                        rawId: this.arrayBufferToBase64(assertion.rawId),
                        authenticatorData: this.arrayBufferToBase64(assertion.response.authenticatorData),
                        clientDataJSON: this.arrayBufferToBase64(assertion.response.clientDataJSON),
                        signature: this.arrayBufferToBase64(assertion.response.signature)
                    }),
                    userId: challengeResponse.data.userId
                });

                if (verifyResponse.success) {
                    window.location.reload();
                    return true;
                } else {
                    throw new Error(verifyResponse.data.message);
                }

            } catch (error) {
                console.error('WebAuthn authentication failed:', error);
                alert('Authentication failed: ' + error.message);
                return false;
            }
        },

        /**
         * Helper: Base64 to ArrayBuffer
         */
        base64ToArrayBuffer: function (base64) {
            const binaryString = window.atob(base64);
            const bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            return bytes.buffer;
        },

        /**
         * Helper: ArrayBuffer to Base64
         */
        arrayBufferToBase64: function (buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return window.btoa(binary);
        }
    };

    // Expose to global
    window.OTPWebAuthn = OTPWebAuthn;

    // Auto-bind events
    $(document).ready(function () {
        // Add biometric login button if supported
        if (OTPWebAuthn.isSupported()) {
            $('.otp-login-form').append(
                '<button type="button" class="otp-webauthn-login" style="margin-top:10px;">' +
                '<span class="dashicons dashicons-fingerprint"></span> Login with Biometrics' +
                '</button>'
            );

            $('.otp-webauthn-login').on('click', function () {
                const username = $('#otp-identifier').val();
                if (!username) {
                    alert('Please enter your username first');
                    return;
                }
                OTPWebAuthn.authenticate(username);
            });
        }
    });

})(jQuery);
