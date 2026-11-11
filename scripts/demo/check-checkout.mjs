import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';
const root = fileURLToPath(new URL('../../', import.meta.url));
const baseURL = process.env.DEMO_BASE_URL;
const envFile = process.env.CHECKOUT_TEST_ENV_FILE;
if (!baseURL || !envFile || process.env.CHECKOUT_TEST_PROJECT !== 'asp-checkout-review') throw new Error('Requires the disposable asp-checkout-review stack and its environment file.');
const demo = JSON.parse(execFileSync('php', ['-r', 'require "resources/php/class/DemoAccounts.php"; echo json_encode(["password" => DemoAccounts::PASSWORD, "users" => DemoAccounts::USERS]);'], { cwd: root, encoding: 'utf8' }));
const browser = await chromium.launch({ channel: process.env.DEMO_BROWSER_CHANNEL || 'chrome', headless: true });
await mkdir(root + '/docs/screenshots', { recursive: true });
try {
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  page.setDefaultTimeout(15000);
  await page.goto(baseURL, { waitUntil: 'domcontentloaded' });
  await page.locator('#username').fill(demo.users.Dealer);
  await page.locator('#password').fill(demo.password);
  await page.locator('#loginButton').click();
  await page.waitForURL('**/page/Dealer/viewSparePart.php');
  async function addPart() {
    const status = await page.evaluate(async () => {
      const response = await fetch('/api/cart/api?action=add', { method: 'POST', headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + window.SLMC_AUTH.getToken() }, body: JSON.stringify({ partId: '5', quantity: 1 }) });
      return response.status;
    });
    assert.equal(status, 200);
    await page.goto(baseURL + '/page/Dealer/shoppingCart.php');
    await page.screenshot({ path: '/tmp/asp-checkout-before.jpg' });
    console.log('Cart loaded; waiting for checkout readiness.');
    await page.locator('#checkoutButton:enabled').waitFor({ timeout: 10000 });
  }
  await addPart();
  await page.screenshot({ path: root + '/docs/screenshots/cart-populated.jpg', type: 'jpeg', quality: 80, animations: 'disabled' });
  let release; const gate = new Promise(resolve => { release = resolve; });
  let submissions = 0;
  await page.route('**/shoppingCart.php', async route => {
    if (route.request().method() === 'POST') { submissions++; await gate; }
    await route.continue();
  });
  console.log('Submitting checkout.');
  const click = page.locator('#checkoutButton').click({ noWaitAfter: true });
  await page.waitForFunction(() => document.querySelector('#checkoutProgress')?.hidden === false);
  assert.equal(await page.evaluate(() => document.querySelector('#checkoutButton').disabled), true);
  await page.screenshot({ path: root + '/docs/screenshots/checkout-loading.jpg', type: 'jpeg', quality: 80, animations: 'disabled' });
  release(); await click;
  await page.waitForURL('**/page/Dealer/checkout.php');
  await page.screenshot({ path: root + '/docs/screenshots/checkout-confirmed.jpg', type: 'jpeg', quality: 80, animations: 'disabled' });
  await page.waitForURL('**/OrderDetails.html?id=*');
  const orderURL = page.url();
  assert.equal(submissions, 1);
  await page.reload();
  assert.equal(page.url(), orderURL);
  assert.equal(submissions, 1);
  console.log('PASS: checkout busy state, one submission, confirmed transition, order-details redirect and refresh without resubmission.');
  await page.unroute('**/shoppingCart.php');
  await addPart();
  execFileSync('docker', ['compose', '--env-file', envFile, '-p', 'asp-checkout-review', '-f', root + '/docker-compose.yml', 'stop', 'api'], { cwd: root, stdio: 'pipe' });
  await page.locator('#checkoutButton').click();
  await page.locator('#checkoutFeedback').getByText('Failed to place order.', { exact: true }).waitFor();
  assert.equal(new URL(page.url()).pathname, '/page/Dealer/shoppingCart.php');
  await page.locator('#checkoutButton:enabled').waitFor();
  console.log('PASS: real shipping-service failure stays on cart, shows an error, and restores the checkout control.');
} finally { await browser.close(); }
