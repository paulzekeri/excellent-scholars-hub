{{--
    "Install app" button (PWA).

    Chrome / Edge fire `beforeinstallprompt` once per page life; the layout's
    esh-pwa.js keeps it in window.eshInstallPrompt and raises
    `esh:install-available`. This element turns that into a one-click install and
    stays hidden when

      * the app already runs as an installed app (display-mode: standalone), or
      * the browser never offers the prompt and it is not iOS.

    iOS Safari never fires `beforeinstallprompt`, so there the button is replaced
    by the "Share > Add to Home Screen" instructions.

    Include it anywhere with:  @include('partials.pwa-install-button')
--}}
<div data-pwa-install class="hidden">
    <button type="button" data-pwa-install-button
            class="hidden items-center gap-2 rounded-lg bg-orange-500 px-3 py-2 text-sm font-semibold text-blue-950 transition hover:bg-orange-400"
            title="Install the app on this device">
        <i class="fas fa-download text-xs"></i>
        <span class="hidden sm:inline">Install app</span>
    </button>

    <p data-pwa-install-ios class="hidden max-w-[16rem] text-xs leading-snug text-blue-100">
        On iPhone or iPad: tap <strong>Share</strong> in the browser bar, then
        <strong>Add to Home Screen</strong>.
    </p>

    <p data-pwa-install-status class="hidden text-xs text-blue-100"></p>
</div>

@push('scripts')
<script>
    (function () {
        'use strict';

        var root = document.querySelector('[data-pwa-install]');
        if (!root) return;

        var button = root.querySelector('[data-pwa-install-button]');
        var iosHint = root.querySelector('[data-pwa-install-ios]');
        var status = root.querySelector('[data-pwa-install-status]');

        function isInstalled() {
            return Boolean(window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                || window.navigator.standalone === true;
        }

        function isIos() {
            return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
        }

        function reveal() {
            if (isInstalled()) {
                root.classList.add('hidden');
                return;
            }

            if (window.eshInstallPrompt) {
                root.classList.remove('hidden');
                button.classList.remove('hidden');
                button.classList.add('inline-flex');
                iosHint.classList.add('hidden');
                return;
            }

            if (isIos()) {
                root.classList.remove('hidden');
                button.classList.add('hidden');
                iosHint.classList.remove('hidden');
            }
        }

        button.addEventListener('click', function () {
            var prompt = window.eshInstallPrompt;
            if (!prompt) return;

            button.disabled = true;
            prompt.prompt();

            prompt.userChoice.then(function (choice) {
                window.eshInstallPrompt = null;
                button.disabled = false;

                if (choice && choice.outcome === 'accepted') {
                    root.classList.add('hidden');
                    return;
                }

                status.classList.remove('hidden');
                status.textContent = 'Install cancelled - you can install later from the browser menu.';
            });
        });

        window.addEventListener('esh:install-available', reveal);
        window.addEventListener('esh:installed', function () {
            root.classList.add('hidden');
        });

        reveal();
    })();
</script>
@endpush
