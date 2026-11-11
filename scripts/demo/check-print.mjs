import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';

const root = fileURLToPath(new URL('../../', import.meta.url));
const base = process.env.DEMO_BASE_URL || 'http://127.0.0.1:8880';
const orderId = process.env.PRINT_TEST_ORDER_ID || '1';
assert.match(orderId, /^[1-9][0-9]*$/);
const demo = JSON.parse(execFileSync('php', ['-r', 'require "resources/php/class/DemoAccounts.php"; echo json_encode(["password"=>DemoAccounts::PASSWORD,"email"=>DemoAccounts::USERS["Sales manager"]]);'], { cwd: root, encoding: 'utf8' }));
const browser = await chromium.launch({ channel: process.env.DEMO_BROWSER_CHANNEL || 'chrome', headless: true });
try {
  const page = await browser.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto(base);
  await page.locator('#username').fill(demo.email);
  await page.locator('#password').fill(demo.password);
  await page.locator('#loginButton').click();
  await page.waitForURL('**/page/Staff/order.html');
  await page.goto(`${base}/page/Staff/orderinfo.html?OrderId=${orderId}`);
  await page.waitForFunction(() => document.querySelector('#orderSummary')?.textContent.includes('Total Cost:'));
  const summary = await page.locator('#orderSummary').innerText();
  await page.evaluate(() => {
    window.printCalls = 0;
    window.print = () => { window.printCalls++; };
    window.originalPrintRegion = document.querySelector('#staffMain');
  });
  await page.locator('#printBtn').click();
  assert.equal(await page.evaluate(() => window.printCalls), 1);
  assert.equal(await page.evaluate(() => document.querySelector('#staffMain') === window.originalPrintRegion), true);
  assert.equal(await page.locator('#orderSummary').innerText(), summary);
  const dark = await page.evaluate(() => document.documentElement.classList.contains('dark'));
  await page.locator('#darkModeToggle').click();
  assert.equal(await page.evaluate(() => document.documentElement.classList.contains('dark')), !dark);
  await page.emulateMedia({ media: 'print' });
  assert.equal(await page.locator('.staff-sidebar').isVisible(), false);
  assert.equal(await page.locator('.staff-header').isVisible(), false);
  assert.equal(await page.locator('#orderActions').isVisible(), false);
  assert.equal(await page.locator('#orderSummary').isVisible(), true);
  assert.equal(await page.locator('#orderItemsDiv').isVisible(), true);
  assert.deepEqual(errors, []);
  console.log('PASS: print invokes once, keeps DOM/listeners and receipt content, and hides staff navigation/actions.');
} finally {
  await browser.close();
}
