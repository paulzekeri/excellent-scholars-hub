{{--
    "Install app" button (PWA).

    Chrome / Edge fire `beforeinstallprompt` once per page life; the layout's
    esh-pwa.js keeps it in window.eshInstallPrompt and raises
    `esh:install-available`. This element turns that into a one-click install.

    iOS Safari never fires `beforeinstallprompt`, so there the same orange
    button is shown and tapping it opens the "Share > Add to Home Screen"
    instructions in a panel under the top bar. The button is hidden only when

      * the app already runs as an installed app (display-mode: standalone), or
      * the browser offers neither an install prompt nor the iOS route.

    Include it anywhere with:  @include('partials.pwa-install-button')
--}}
<div data-pwa-install class="relative hidden">
    <button type="button" data-pwa-install-button
            aria-label="Install the app on this device"
            aria-controls="esh-pwa-install-help" aria-expanded="false"
            class="hidden items-center gap-2 rounded-lg bg-orange-500 px-2.5 py-2 text-sm font-semibold text-blue-950 transition hover:bg-orange-400 sm:px-3"
            title="Install the app on this device">
        <i class="fas fa-download text-xs" aria-hidden="true"></i>
        <span class="hidden sm:inline">Install app</span>
    </button>

    <div id="esh-pwa-install-help" data-pwa-install-ios
         class="fixed right-3 top-[4.5rem] z-50 hidden w-72 max-w-[calc(100vw-1.5rem)] rounded-xl border border-white/15 bg-[#0d3070] p-4 text-left shadow-2xl sm:right-6 lg:right-8">
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-semibold text-white">
                <i class="fas fa-mobile-alt mr-1" aria-hidden="true"></i> Add the app to your Home Screen
            </p>
            <button type="button" data-pwa-install-dismiss
                    class="-mr-1 -mt-1 shrink-0 rounded-lg p-1 text-blue-100 transition hover:bg-white/10 hover:text-white"
                    aria-label="Close install instructions">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <ol class="mt-2 list-decimal space-y-1 pl-4 text-xs leading-relaxed text-blue-100">
            <li>Tap <strong>Share</strong> (or the browser's <strong>&#8942;</strong> menu) in the toolbar.</li>
            <li>Scroll and choose <strong>Add to Home Screen</strong>, then tap <strong>Add</strong>.</li>
            <li>Open the app from the Home Screen and sign in.</li>
        </ol>
    </div>

    <p data-pwa-install-status
       class="fixed right-3 top-[4.5rem] z-50 hidden w-64 max-w-[calc(100vw-1.5rem)] rounded-xl bg-white p-3 text-xs text-gray-700 shadow-2xl sm:right-6 lg:right-8"></p>
</div>

@push('scripts')
<script>
    (function () {
        'use strict';

        var root = document.querySelector('[data-pwa-install]');
        if (!root) return;

        var button = root.querySelector('[data-pwa-install-button]');
        var iosHint = root.querySelector('[data-pwa-install-ios]');
        var dismiss = root.querySelector('[data-pwa-install-dismiss]');
        var status = root.querySelector('[data-pwa-install-status]');

        function isInstalled() {
            return Boolean(window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                || window.navigator.standalone === true;
        }

        // iOS Safari never fires beforeinstallprompt. iPadOS 13+ reports a
        // desktop "Macintosh" user agent, so touch support is part of the test.
        function isIos() {
            var ua = window.navigator.userAgent || '';
            var ipadOnDesktopUa = /macintosh/i.test(ua) && (window.navigator.maxTouchPoints || 0) > 1;

            return /iphone|ipad|ipod/i.test(ua) || ipadOnDesktopUa;
        }

        function hideHint() {
            iosHint.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        }

        function showHint() {
            status.classList.add('hidden');
            iosHint.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
        }

        function showButton() {
            root.classList.remove('hidden');
            button.classList.remove('hidden');
            button.classList.add('inline-flex');
        }

        function hideEverything() {
            hideHint();
            status.classList.add('hidden');
            root.classList.add('hidden');
        }

        function reveal() {
            if (isInstalled()) {
                hideEverything();
                return;
            }

            if (window.eshInstallPrompt) {
                showButton();
                hideHint();
                return;
            }

            if (isIos()) {
                showButton();
            }
        }

        button.addEventListener('click', function () {
            var prompt = window.eshInstallPrompt;

            // iOS has no prompt API: the button simply opens the instructions.
            if (!prompt) {
                if (iosHint.classList.contains('hidden')) {
                    showHint();
                } else {
                    hideHint();
                }
                return;
            }

            button.disabled = true;
            prompt.prompt();

            prompt.userChoice.then(function (choice) {
                window.eshInstallPrompt = null;
                button.disabled = false;

                if (choice && choice.outcome === 'accepted') {
                    hideEverything();
                    return;
                }

                status.classList.remove('hidden');
                status.textContent = 'Install cancelled - you can install later from the browser menu.';

                window.setTimeout(function () {
                    status.classList.add('hidden');
                }, 6000);
            });
        });

        dismiss && dismiss.addEventListener('click', function (event) {
            event.stopPropagation();
            hideHint();
        });

        // Tapping anywhere else closes the instructions.
        document.addEventListener('click', function (event) {
            if (!iosHint.classList.contains('hidden') && !root.contains(event.target)) {
                hideHint();
            }
        });

        // Keep the panel from floating over the page while it scrolls.
        window.addEventListener('scroll', function () {
            if (!iosHint.classList.contains('hidden')) {
                hideHint();
            }
        }, { passive: true });

        window.addEventListener('esh:install-available', reveal);
        window.addEventListener('esh:installed', hideEverything);

        reveal();
    })();
</script>
@endpush
