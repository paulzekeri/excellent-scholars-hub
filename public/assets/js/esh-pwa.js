/**
 * PWA plumbing for Excellent Scholars Hub.
 *
 * Two things, both progressive:
 *
 *   1. Install: register public/service-worker.js (which is what makes Chrome /
 *      Edge offer "Install app") and keep the `beforeinstallprompt` event so the
 *      button in resources/views/partials/pwa-install-button.blade.php can install
 *      in one click.
 *   2. Phone alerts: subscribe this browser to Web Push using the VAPID public
 *      key the layout puts in a meta tag, post the endpoint to /push/subscribe,
 *      and let /push/status decide what the button should show.
 *
 * Every call is guarded: an unsupported browser, a blocked permission or a
 * server without keys simply leaves the UI quiet. See PWA.md.
 */
(function () {
    'use strict';

    var swRegistration = null;

    function serviceWorker() {
        if (!('serviceWorker' in navigator)) {
            return Promise.reject(new Error('This browser has no service worker support.'));
        }

        if (!swRegistration) {
            swRegistration = navigator.serviceWorker
                .register('/service-worker.js')
                .catch(function (error) {
                    swRegistration = null;
                    throw error;
                });
        }

        return swRegistration.then(function () {
            return navigator.serviceWorker.ready;
        });
    }

    // ---------------------------------------------------------- Install app
    // Chrome / Edge fire this once per page life; keeping the event is the only
    // way to offer an in-page install button instead of the browser menu.
    window.eshInstallPrompt = null;

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        window.eshInstallPrompt = event;
        window.dispatchEvent(new CustomEvent('esh:install-available'));
    });

    window.addEventListener('appinstalled', function () {
        window.eshInstallPrompt = null;
        window.dispatchEvent(new CustomEvent('esh:installed'));
    });

    // --------------------------------------------------------- Phone alerts
    function meta(name) {
        var tag = document.querySelector('meta[name="' + name + '"]');
        return tag ? tag.getAttribute('content') : '';
    }

    function csrfToken() {
        return meta('csrf-token');
    }

    /** base64url VAPID key → Uint8Array, which is what pushManager wants. */
    function keyBytes(base64) {
        var padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4))
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        var raw = window.atob(padded);
        var bytes = new Uint8Array(raw.length);

        for (var i = 0; i < raw.length; i++) {
            bytes[i] = raw.charCodeAt(i);
        }

        return bytes;
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body || {})
        });
    }

    var emptyStatus = { configured: false, subscribed: false, devices: 0 };

    var push = {
        configured: meta('esh-push-key') !== '',
        key: meta('esh-push-key'),
        statusUrl: meta('esh-push-status-url'),
        subscribeUrl: meta('esh-push-subscribe-url'),
        unsubscribeUrl: meta('esh-push-unsubscribe-url'),

        supported: ('serviceWorker' in navigator) && ('PushManager' in window) && ('Notification' in window),

        permission: function () {
            return ('Notification' in window) ? Notification.permission : 'unsupported';
        },

        isIos: function () {
            return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
        },

        isStandalone: function () {
            return Boolean(window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                || window.navigator.standalone === true;
        },

        /** What this server and this device have, for the button to render from. */
        status: function () {
            if (!push.configured || !push.statusUrl) {
                return Promise.resolve(emptyStatus);
            }

            return fetch(push.statusUrl, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                return response.ok ? response.json() : emptyStatus;
            });
        },

        /** Ask for permission, subscribe this browser and file the endpoint. */
        enable: function () {
            if (!push.configured) {
                return Promise.reject(new Error('Phone alerts are not switched on for this server yet.'));
            }

            if (!push.supported) {
                return Promise.reject(new Error('This browser cannot show phone alerts.'));
            }

            return serviceWorker()
                .then(function (registration) {
                    return Notification.requestPermission().then(function (permission) {
                        if (permission !== 'granted') {
                            throw new Error('Alerts were not allowed. You can allow them in the browser settings, then try again.');
                        }

                        return registration.pushManager.getSubscription().then(function (existing) {
                            if (existing) {
                                return existing;
                            }

                            return registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: keyBytes(push.key)
                            });
                        });
                    });
                })
                .then(function (subscription) {
                    return post(push.subscribeUrl, push.payloadFor(subscription)).then(function (response) {
                        if (!response.ok) {
                            throw new Error('That device could not be registered. Try again in a moment.');
                        }

                        return response.json();
                    });
                });
        },

        /** Forget this device (the endpoint is removed server-side too). */
        disable: function () {
            return serviceWorker().then(function (registration) {
                return registration.pushManager.getSubscription().then(function (subscription) {
                    if (!subscription) {
                        return null;
                    }

                    return post(push.unsubscribeUrl, { endpoint: subscription.endpoint })
                        .catch(function () { /* the local unsubscribe below still runs */ })
                        .then(function () {
                            return subscription.unsubscribe();
                        });
                });
            });
        },

        /**
         * Runs on every page load: a browser may rotate the endpoint, so a live
         * permission is re-posted (the server keys rows by the endpoint hash).
         */
        sync: function () {
            if (!push.configured || !push.supported || push.permission() !== 'granted') {
                return Promise.resolve();
            }

            return serviceWorker().then(function (registration) {
                return registration.pushManager.getSubscription().then(function (subscription) {
                    if (!subscription) {
                        return null;
                    }

                    return post(push.subscribeUrl, push.payloadFor(subscription)).catch(function () {});
                });
            }).catch(function () {});
        },

        /** Exactly the shape PushSubscription.toJSON() gives the page. */
        payloadFor: function (subscription) {
            var json = subscription.toJSON();

            return {
                endpoint: json.endpoint,
                keys: json.keys,
                contentEncoding: 'aes128gcm'
            };
        }
    };

    window.EshPwa = {
        serviceWorker: serviceWorker,
        installPrompt: function () {
            return window.eshInstallPrompt;
        },
        push: push
    };

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', function (event) {
            if (event.data && event.data.type === 'esh:push-resubscribe') {
                window.EshPwa.push.sync();
            }
        });
    }

    serviceWorker().catch(function () { /* pages still work without it */ });

    // Refresh an existing subscription on every page load (endpoints rotate).
    push.sync();
})();
