import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';
const root = fileURLToPath(new URL('../../', import.meta.url));
const demo = JSON.parse(execFileSync('php', ['-r', 'require "resources/php/class/DemoAccounts.php"; echo json_encode(["password" => DemoAccounts::PASSWORD, "users" => DemoAccounts::USERS]);'], { cwd: root, encoding: 'utf8' }));
const baseURL = process.env.DEMO_BASE_URL || 'http://127.0.0.1:8880';
const browser = await chromium.launch({ channel: process.env.DEMO_BROWSER_CHANNEL || 'chrome', headless: true });
try {
  for (const remember of [false, true]) {
    const context = await browser.newContext({ permissions: ['clipboard-read', 'clipboard-write'] });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(baseURL);
    await page.locator('#demoAccountsOpen').click();
    const account = page.locator('[data-demo-account]').first();
    await account.locator('[data-demo-email]').dblclick();
    await page.waitForFunction(() => document.querySelector('#demoAccountsStatus').textContent === 'Email copied.');
    assert.equal(await page.evaluate(() => navigator.clipboard.readText()), demo.users.Dealer);
    await account.locator('[data-demo-password]').dblclick();
    await page.waitForFunction(() => document.querySelector('#demoAccountsStatus').textContent === 'Password copied.');
    assert.equal(await page.evaluate(() => navigator.clipboard.readText()), demo.password);
    await account.locator('[data-demo-copy]').click();
    await page.waitForFunction(() => document.querySelector('#demoAccountsStatus').textContent === 'Credentials copied.');
    assert.equal(await page.evaluate(() => navigator.clipboard.readText()), `Email: ${demo.users.Dealer}\nPassword: ${demo.password}`);
    await account.locator('[data-demo-fill]').click();
    assert.equal(await page.locator('#username').inputValue(), demo.users.Dealer);
    await page.locator('#remember-me').setChecked(remember);
    await page.locator('#loginButton').click();
    await page.waitForURL('**/page/Dealer/viewSparePart.php', { timeout: 20000 });
    const storage = await page.evaluate(() => ({ local: Boolean(localStorage.getItem('token')), session: Boolean(sessionStorage.getItem('token')) }));
    assert.deepEqual(storage, { local: remember, session: !remember });
    await page.goto(baseURL + '/page/Dealer/profile.html');
    await page.locator('#profileContent').waitFor({ state: 'visible' });
    assert.equal(await page.locator('#email').innerText(), demo.users.Dealer);
    const before = await page.evaluate(() => document.documentElement.classList.contains('dark'));
    await page.locator('#darkModeToggle').click();
    assert.equal(await page.evaluate(() => document.documentElement.classList.contains('dark')), !before);
    assert.equal(await page.evaluate(() => localStorage.getItem('theme')), before ? 'light' : 'dark');
    const editResponse = page.waitForResponse(response => response.url().includes('profile.php?action=getEditInfo'));
    await page.goto(baseURL + '/page/Dealer/profileSetting.html');
    assert.equal((await editResponse).status(), 200);
    const unauthorized = await page.evaluate(async () => {
      const response = await fetch('/api/profile/api?action=get', { headers: { Authorization: 'Bearer invalid-demo-token' } });
      return response.status;
    });
    assert.equal(unauthorized, 401);
    for (const id of ['', '0', '-1', '1.5', 'abc', '999999999999999999999999']) {
      const route = '/page/Dealer/OrderDetails.html' + (id ? '?id=' + encodeURIComponent(id) : '');
      let requests = 0;
      const listener = request => { if (request.url().includes('/api/orders/dealer-get')) requests++; };
      page.on('request', listener);
      await page.goto(baseURL + route, { waitUntil: 'domcontentloaded' });
      await page.locator(id ? '#orderErrorState' : '#orderEmptyState').waitFor({ state: 'visible' });
      assert.equal(requests, 0);
      page.off('request', listener);
      const status = await page.evaluate(async value => {
        const response = await fetch('/api/orders/dealer-get?id=' + encodeURIComponent(value), { headers: { Authorization: 'Bearer ' + window.SLMC_AUTH.getToken() } });
        return response.status;
      }, id);
      assert.equal(status, 400);
    }
    assert.deepEqual(errors, []);
    console.log(`PASS: demo copy/fill, ${remember ? 'persistent' : 'session'} login, profile, settings, theme and invalid-token rejection.`);
    await context.close();
  }
  const mobile = await browser.newPage({ viewport: { width: 390, height: 844 } });
  await mobile.goto(baseURL);
  await mobile.locator('#demoAccountsOpen').click();
  assert.equal(await mobile.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
  await mobile.keyboard.press('Escape');
  assert.equal(await mobile.locator('#demoAccountsDialog').evaluate(dialog => dialog.open), false);
  console.log('PASS: mobile overlay fits viewport and closes with Escape.');
} finally { await browser.close(); }
