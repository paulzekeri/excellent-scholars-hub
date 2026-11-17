#!/usr/bin/env node
/**
 * iPhone/iPad "Install app" button — browser smoke check.
 *
 * The top-bar install button has an iOS-only path: Safari never fires
 * `beforeinstallprompt`, so on iPhone/iPad the button itself has to render and
 * open the "Add to Home Screen" panel. This script renders the real partial
 * (resources/views/partials/pwa-install-button.blade.php) in Playwright's
 * WebKit build and checks every branch of that logic. It runs on Windows,
 * macOS and Linux — no iPhone, no Mac and no Xcode required.
 *
 * One-time setup, from the repository root:
 *
 *   npm --prefix tools/pwa-install-check install
 *   npm --prefix tools/pwa-install-check run install-browser
 *
 * Then:
 *
 *   npm run test:pwa-install
 *
 * Exits non-zero when a branch regresses, so it can gate a release.
 */
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { createServer } from 'node:http';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { devices, webkit } from 'playwright';

const here = path.dirname(fileURLToPath(import.meta.url));
const partialPath = path.resolve(here, '../../resources/views/partials/pwa-install-button.blade.php');

const IPHONE_UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_6 like Mac OS X) AppleWebKit/605.1.15 '
    + '(KHTML, like Gecko) Version/17.6 Mobile/15E148 Safari/604.1';
const IPADOS_DESKTOP_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 '
    + '(KHTML, like Gecko) Version/17.6 Safari/605.1.15';

const IPHONE = { ...devices['iPhone 13'], userAgent: IPHONE_UA };

/**
 * Turn the Blade partial into a standalone page. The partial has no Blade
 * echoes, only comments and the @push('scripts') wrapper, so a light strip is
 * enough — and a guard fails loudly if that ever stops being true.
 */
async function buildFixture() {
    const source = await readFile(partialPath, 'utf8');

    const html = source
        .replace(/\{\{--[\s\S]*?--\}\}/g, '')
        .replace(/@push\([^)]*\)/g, '')
        .replace(/@endpush/g, '');

    if (/\{\{|\}\}|@\w+/.test(html)) {
        throw new Error('pwa-install-button.blade.php now contains Blade syntax the fixture cannot render.');
    }

    return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Install button smoke check</title>
<style>
    /* The app ships Tailwind from a CDN; this check only needs its display helpers. */
    .hidden { display: none !important; }
    .inline-flex { display: inline-flex !important; }
    body { margin: 0; padding: 8px; font-family: sans-serif; }
</style>
</head>
<body>${html}</body>
</html>`;
}

const pageHtml = await buildFixture();

// The partial's script must run in a normal document; a local server is the
// most faithful way to load it (and keeps init scripts working for reloads).
const server = createServer((request, response) => {
    response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' });
    response.end(pageHtml);
});

await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const url = `http://127.0.0.1:${server.address().port}/`;

const browser = await webkit.launch();
let failures = 0;

async function open(contextOptions, initScript) {
    const context = await browser.newContext(contextOptions);

    if (initScript) {
        await context.addInitScript(initScript);
    }

    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto(url, { waitUntil: 'load' });

    return { context, page, errors };
}

function classed(page, selector, className) {
    return page.locator(selector).evaluate((el, name) => el.classList.contains(name), className);
}

async function run(name, scenario) {
    try {
        await scenario();
        console.log(`PASS  ${name}`);
    } catch (error) {
        failures += 1;
        console.error(`FAIL  ${name}\n      ${error.message}`);
    }
}

await run('iPhone Safari: the button is visible and opens the Home Screen steps', async () => {
    const { context, page, errors } = await open(IPHONE);

    try {
        const button = page.locator('[data-pwa-install-button]');
        const steps = page.locator('[data-pwa-install-ios]');

        assert.equal(await button.isVisible(), true, 'The install button should be visible on iPhone Safari.');
        assert.equal(await classed(page, '[data-pwa-install-button]', 'hidden'), false);
        assert.equal(await steps.isVisible(), false, 'The steps should start closed.');

        await button.click();
        assert.equal(await steps.isVisible(), true, 'Tapping the button should open the steps.');
        assert.match(await steps.innerText(), /Add to Home Screen/);
        assert.equal(await button.getAttribute('aria-expanded'), 'true');

        await page.locator('[data-pwa-install-dismiss]').click();
        assert.equal(await steps.isVisible(), false, 'The close button should hide the steps.');
        assert.equal(await button.getAttribute('aria-expanded'), 'false');

        await button.click();
        await page.mouse.click(200, 300);
        assert.equal(await steps.isVisible(), false, 'Tapping elsewhere should hide the steps.');

        assert.deepEqual(errors, [], 'The page raised a JavaScript error.');
    } finally {
        await context.close();
    }
});

await run('iPadOS (desktop Macintosh user agent + touch): the button is visible', async () => {
    const { context, page, errors } = await open({ ...IPHONE, userAgent: IPADOS_DESKTOP_UA }, () => {
        Object.defineProperty(window.navigator, 'maxTouchPoints', { configurable: true, get: () => 5 });
    });

    try {
        assert.equal(await page.locator('[data-pwa-install-button]').isVisible(), true,
            'iPadOS reports a Macintosh UA; the touch-point check should still show the button.');
        assert.deepEqual(errors, [], 'The page raised a JavaScript error.');
    } finally {
        await context.close();
    }
});

await run('Installed app (standalone): the button stays hidden', async () => {
    const { context, page, errors } = await open(IPHONE, () => {
        Object.defineProperty(window.navigator, 'standalone', { configurable: true, get: () => true });
    });

    try {
        assert.equal(await page.locator('[data-pwa-install]').isVisible(), false,
            'An installed app must not offer the install button.');
        assert.equal(await classed(page, '[data-pwa-install]', 'hidden'), true);
        assert.deepEqual(errors, [], 'The page raised a JavaScript error.');
    } finally {
        await context.close();
    }
});

await run('Desktop browser without an install prompt: the button stays hidden', async () => {
    const { context, page, errors } = await open({ viewport: { width: 1280, height: 800 } });

    try {
        assert.equal(await page.locator('[data-pwa-install]').isVisible(), false,
            'A browser with no prompt and no iOS route must not show the button.');
        assert.deepEqual(errors, [], 'The page raised a JavaScript error.');
    } finally {
        await context.close();
    }
});

await run('Chrome/Android prompt: one tap installs and hides the button', async () => {
    const { context, page, errors } = await open({ viewport: { width: 1280, height: 800 } });

    try {
        await page.evaluate(() => {
            window.__eshPromptCalled = false;
            window.eshInstallPrompt = {
                prompt: () => { window.__eshPromptCalled = true; },
                userChoice: Promise.resolve({ outcome: 'accepted' }),
            };
            window.dispatchEvent(new CustomEvent('esh:install-available'));
        });

        const button = page.locator('[data-pwa-install-button]');
        assert.equal(await button.isVisible(), true, 'A captured prompt should reveal the button.');

        await button.click();
        await page.waitForFunction(() => window.__eshPromptCalled === true);
        await page.waitForFunction(() => document.querySelector('[data-pwa-install]').classList.contains('hidden'));

        assert.deepEqual(errors, [], 'The page raised a JavaScript error.');
    } finally {
        await context.close();
    }
});

await browser.close();
server.close();

if (failures > 0) {
    console.error(`\n${failures} install-button check(s) failed.`);
    process.exitCode = 1;
} else {
    console.log('\nAll install-button checks passed.');
}
