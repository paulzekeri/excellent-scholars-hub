{{--
    "Enable phone alerts" (Web Push) — the button that asks the browser for the
    notification permission and files this device's push endpoint.

    Rendered only when this server can push at all (a VAPID pair in .env and the
    /push/* routes), which is the same guard the layout uses. Everything else is
    decided in the browser by public/assets/js/esh-pwa.js: a page cannot ask for
    the Notification permission by itself (browsers require a user gesture), and
    the block steps aside once alerts are on.

    Include it anywhere with:  @include('partials.phone-alerts-button')
--}}
@php
    $eshPushKey = app(\App\Services\WebPushService::class)->publicKey();

    // Route::has() keeps a stale route cache from breaking every page.
    $eshPushReady = $eshPushKey !== null
        && \Illuminate\Support\Facades\Route::has('push.status')
        && \Illuminate\Support\Facades\Route::has('push.subscribe');
@endphp

@if($eshPushReady)
<div data-phone-alerts class="hidden rounded-xl border border-blue-200 bg-blue-50 p-4 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-blue-900">
                <i class="fas fa-mobile-alt mr-1"></i> Phone alerts
            </p>
            <p data-phone-alerts-state class="text-sm text-blue-800 mt-0.5">
                Get a notification on this device the moment something new arrives &mdash; even when the app is closed.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-phone-alerts-button
                    class="bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-800 transition">
                <i class="fas fa-bell mr-1"></i> Enable phone alerts
            </button>
            <button type="button" data-phone-alerts-disable
                    class="hidden bg-white border border-blue-600 text-blue-700 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-50 transition">
                Turn off
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        'use strict';

        var root = document.querySelector('[data-phone-alerts]');
        var push = window.EshPwa && window.EshPwa.push;

        if (!root || !push) return;

        var button = root.querySelector('[data-phone-alerts-button]');
        var off = root.querySelector('[data-phone-alerts-disable]');
        var state = root.querySelector('[data-phone-alerts-state]');

        function say(message, tone) {
            root.classList.remove('hidden');
            state.textContent = message;
            state.className = 'text-sm mt-0.5 ' + (tone || 'text-blue-800');
        }

        function showEnable(on) {
            button.classList.toggle('hidden', !on);
        }

        function showDisable(on) {
            off.classList.toggle('hidden', !on);
        }

        function render() {
            if (!push.configured) {
                root.classList.add('hidden');
                return;
            }

            if (!push.supported) {
                say('This browser cannot show phone alerts. On iPhone, add the app to your Home Screen first, then try again.', 'text-orange-800');
                showEnable(false);
                showDisable(false);
                return;
            }

            push.status().then(function (data) {
                if (data.subscribed) {
                    say('Phone alerts are on for this device' + (data.devices > 1 ? ' (' + data.devices + ' devices on file).' : '.'));
                    showEnable(false);
                    showDisable(true);
                    return;
                }

                if (push.permission() === 'denied') {
                    say('Alerts are blocked for this site. Allow notifications in your browser settings, then reload this page.', 'text-orange-800');
                    showEnable(false);
                    showDisable(false);
                    return;
                }

                if (push.isIos() && !push.isStandalone()) {
                    say('On iPhone or iPad: first add the app to your Home Screen (Share \u2192 Add to Home Screen), open it from there, then turn on phone alerts.', 'text-blue-900');
                } else {
                    say('Get a notification on this device the moment something new arrives \u2014 even when the app is closed.');
                }

                showEnable(true);
                showDisable(false);
            }).catch(function () {
                root.classList.add('hidden');
            });
        }

        button.addEventListener('click', function () {
            button.disabled = true;
            state.textContent = 'Asking the browser for permission\u2026';

            push.enable().then(function () {
                button.disabled = false;
                render();
            }).catch(function (error) {
                button.disabled = false;
                say(error && error.message ? error.message : 'Phone alerts could not be switched on.', 'text-orange-800');
            });
        });

        off.addEventListener('click', function () {
            off.disabled = true;

            push.disable().then(function () {
                off.disabled = false;
                render();
            }).catch(function () {
                off.disabled = false;
                say('This device could not be switched off. Try again later.', 'text-orange-800');
            });
        });

        render();
    })();
</script>
@endpush
@endif
