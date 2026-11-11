import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
const root = fileURLToPath(new URL('../../', import.meta.url));
const baseURL = process.env.DEMO_BASE_URL || 'http://127.0.0.1:8880';
const output = path.join(root, 'docs/screenshots');
const demo = JSON.parse(execFileSync('php', ['-r', 'require "resources/php/class/DemoAccounts.php"; echo json_encode(["password" => DemoAccounts::PASSWORD, "users" => DemoAccounts::USERS]);'], { cwd: root, encoding: 'utf8' }));
await mkdir(output, { recursive: true });
const browser = await chromium.launch({ channel: process.env.DEMO_BROWSER_CHANNEL || 'chrome', headless: true });
const manifest = { baseURL, capturedAt: new Date().toISOString(), viewport: { width: 1440, height: 1000 }, pages: [] };
async function context() {
  const ctx = await browser.newContext({ viewport: manifest.viewport, colorScheme: 'light', permissions: ['clipboard-read', 'clipboard-write'] });
  await ctx.addInitScript(() => { localStorage.setItem('theme', 'light'); });
  return ctx;
}
async function capture(page, name, url, title) {
  const failures = [];
  const handler = response => {
    if (response.status() >= 400 && response.url().startsWith(baseURL) && !(name.startsWith('error-') && response.request().resourceType() === 'document' && response.status() === Number(name.slice(6)))) failures.push({ path: new URL(response.url()).pathname, status: response.status() });
  };
  page.setDefaultTimeout(15000);
  page.on('response', handler);
  page.on('dialog', dialog => dialog.dismiss());
  const response = await page.goto(baseURL + url, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForLoadState("networkidle", { timeout: 5000 }).catch(() => {});
  await page.waitForTimeout(1000);
  await page.screenshot({ path: path.join(output, name + '.jpg'), fullPage: true, animations: 'disabled', type: 'jpeg', quality: 78 });
  page.off('response', handler);
  const item = { name, title, expectedHttp: name.startsWith('error-') ? Number(name.slice(6)) : 200, route: url, finalPath: new URL(page.url()).pathname, http: response.status(), failures };
  manifest.pages.push(item);
  console.log(`${name}: HTTP ${item.http}; ${failures.length} failed local requests`);
}
async function login(page, role) {
  await page.goto(baseURL, { waitUntil: 'networkidle' });
  await page.locator('#username').fill(demo.users[role]);
  await page.locator('#password').fill(demo.password);
  await page.locator('#loginButton').click();
  await page.waitForURL('**/page/**', { timeout: 20000 });
}
try {
  const publicContext = await context();
  const page = await publicContext.newPage();
  await capture(page, 'login', '/', 'Sign in');
  await page.locator('#demoAccountsOpen').click();
  await page.screenshot({ path: path.join(output, 'demo-accounts.jpg'), animations: 'disabled', type: 'jpeg', quality: 82 });
  manifest.pages.push({ name: 'demo-accounts', title: 'Demo account overlay', route: '/', http: 200, failures: [] });
  await page.locator('#demoAccountsClose').click();
  await page.locator('#darkModeToggle').click();
  await page.screenshot({ path: path.join(output, 'login-dark.jpg'), animations: 'disabled', type: 'jpeg', quality: 80 });
  manifest.pages.push({ name: 'login-dark', title: 'Sign in — dark theme', route: '/', http: 200, failures: [] });
  await page.locator('#darkModeToggle').click();
  for (const [name, title] of [['privacy-policy', 'Privacy Policy'], ['terms-of-use', 'Terms of Use'], ['qa', 'Q&A'], ['about', 'About SLMC']]) {
    await capture(page, name, `/${name}.php`, title);
  }
  for (const code of [403, 404, 500, 503]) await capture(page, 'error-' + code, '/resources/php/error.php?code=' + code, 'Error ' + code);
  const dealerContext = await context(); const dealer = await dealerContext.newPage(); await login(dealer, 'Dealer');
  for (const [name, route, title] of [
    ['dealer-catalog', 'viewSparePart.php', 'Spare parts catalog'],
    ['dealer-part', 'sparePart-template.html?id=1', 'Part details'],
    ['dealer-cart', 'shoppingCart.php', 'Shopping cart'],
    ['dealer-orders', 'viewOrderRecord.html', 'Order history'],
    ['dealer-order', 'OrderDetails.html?id=1', 'Order details'],
    ['dealer-profile', 'profile.html', 'Profile'],
    ['dealer-profile-settings', 'profileSetting.html', 'Profile settings'],
  ]) await capture(dealer, name, '/page/Dealer/' + route, title);
  const staffContext = await context(); const staff = await staffContext.newPage(); await login(staff, 'Sales manager');
  for (const [name, route, title] of [
    ['staff-orders', 'order.html', 'Staff orders'], ['staff-order', 'orderinfo.html?OrderId=1', 'Staff order details'],
    ['staff-items', 'Item.html', 'Catalog management'], ['staff-add-item', 'addItem.html', 'Add item'],
    ['staff-edit-item', 'editItem.html?id=1', 'Edit item'], ['staff-report', 'genreport.html', 'Reports'],
  ]) await capture(staff, name, '/page/Staff/' + route, title);
  await capture(dealer, 'logout', '/logout.php', 'Sign out');
  await capture(page, 'logout-static', '/logout.html', 'Signed out');
} finally {
  await writeFile(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
  await browser.close();
}
