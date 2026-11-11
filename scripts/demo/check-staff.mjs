import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { writeFile } from 'node:fs/promises';
import assert from 'node:assert/strict';
const root = fileURLToPath(new URL('../../', import.meta.url));
const baseURL = process.env.DEMO_BASE_URL;
if (!baseURL || process.env.STAFF_TEST_PROJECT !== 'asp-staff-review') throw new Error('Use only the disposable asp-staff-review stack.');
const demo = JSON.parse(execFileSync('php', ['-r', 'require "resources/php/class/DemoAccounts.php"; echo json_encode(["password" => DemoAccounts::PASSWORD, "users" => DemoAccounts::USERS]);'], { cwd: root, encoding: 'utf8' }));
const browser = await chromium.launch({ channel: process.env.DEMO_BROWSER_CHANNEL || 'chrome', headless: true });
const passed = [];
function pass(message) { passed.push(message); console.log('PASS: ' + message); }
try {
 const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
 page.setDefaultTimeout(15000);
 const errors = []; page.on('pageerror', error => errors.push(error.message));
 page.on('dialog', dialog => dialog.accept());
 await page.goto(baseURL, { waitUntil: 'domcontentloaded' });
 await page.locator('#username').fill(demo.users['Sales manager']); await page.locator('#password').fill(demo.password); await page.locator('#loginButton').click(); await page.waitForURL('**/page/Staff/order.html');
 async function api(action, data, method = 'POST') {
  return page.evaluate(async ({ action, data, method }) => {
   const response = await fetch('/resources/php/ManageItem.php?action=' + action, { method, headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + window.SLMC_AUTH.getToken() }, ...(method === 'GET' ? {} : { body: JSON.stringify(data) }) });
   return { status: response.status, body: await response.json() };
  }, { action, data, method });
 }
 await page.goto(baseURL + '/page/Staff/addItem.html', { waitUntil: 'domcontentloaded' });
 await page.waitForFunction(() => /^\d+$/.test(document.querySelector('#NewSparePart-id').textContent.trim()));
 const id = Number((await page.locator('#NewSparePart-id').innerText()).trim());
 await page.locator('#category').selectOption('1'); await page.locator('#input-part-no').fill('Staff workflow test product');
 await page.locator('#input-weight').fill('1'); await page.locator('#input-stock-quantity').fill('10'); await page.locator('#input-price').fill('123.45'); await page.locator('#description').fill('Disposable staff test fixture');
 await page.locator('#addItemForm button[type=submit]').click(); await page.waitForURL('**/page/Staff/Item.html');
 let item = await api('get&id=' + id, null, 'GET'); assert.equal(item.body.data.sparePartName, 'Staff workflow test product'); pass('Add product through the staff form and persist its values');
 await page.locator('#checkbox-table-' + id).check(); await page.getByRole('button', { name: 'Edit Item', exact: true }).click(); await page.waitForURL('**/editItem.html?id=' + id);
 await page.waitForFunction(value => document.querySelector('#sparePartNumber').textContent.trim() === String(value), id);
 await page.locator('#input-stock-quantity').fill('8'); await page.locator('#input-price').fill('145.50'); await page.locator('#description').fill('Updated staff test fixture'); await page.locator('button[type=submit]').click(); await page.waitForURL('**/page/Staff/Item.html');
 item = await api('get&id=' + id, null, 'GET'); assert.equal(Number(item.body.data.price), 145.5); assert.equal(Number(item.body.data.stockItemQty), 8); pass('Edit product through the staff form and verify saved stock and price');
 const single = await api('delete', { id: 1 }); assert.equal(single.status, 409); assert.equal(single.body.code, 'PRODUCT_IN_ORDER');
 const mixed = await api('BatchDelete', { ids: [id, 1] }); assert.equal(mixed.status, 409); assert.ok(mixed.body.lockedItemIds.includes(1)); assert.equal((await api('get&id=' + id, null, 'GET')).body.status, 'success'); pass('Single and mixed-batch deletion reject ordered products and preserve the entire batch');
 await page.locator('#checkbox-table-1').check(); await page.getByRole('button', { name: 'Delete Item', exact: true }).click(); await page.getByRole('button', { name: 'Delete products', exact: true }).click();
 await page.getByRole('heading', { name: 'Products locked by orders' }).waitFor(); await page.screenshot({ path: root + '/docs/screenshots/staff-product-locked.jpg', animations: 'disabled', type: 'jpeg', quality: 80 }); await page.getByRole('button', { name: 'Close', exact: true }).click();
 await page.locator('#checkbox-table-1').uncheck(); await page.locator('#checkbox-table-' + id).check(); await page.getByRole('button', { name: 'Delete Item', exact: true }).click(); await page.getByRole('button', { name: 'Delete products', exact: true }).click(); await page.getByRole('heading', { name: 'Products deleted', exact: true }).waitFor(); await page.getByRole('button', { name: 'Close', exact: true }).click(); await page.locator('#checkbox-table-' + id).waitFor({ state: 'detached' }); pass('Deletion modal explains the lock; deleting an unreferenced product succeeds through the UI');
 const pendingId = await page.evaluate(async () => { const response = await fetch('/api/orders/manager-list', { headers: { Authorization: 'Bearer ' + window.SLMC_AUTH.getToken() } }); const result = await response.json(); return result.data.orders.find(order => order.orderStatus === 'Pending')?.orderID; });
 assert.ok(pendingId, 'Run the product-deletion-lock runtime check first to create a pending order.');
 await page.goto(baseURL + '/page/Staff/orderinfo.html?OrderId=' + pendingId, { waitUntil: 'domcontentloaded' });
 for (const [button, status] of [['confirmBtn', 'Confirmed'], ['shippedBtn', 'Shipped'], ['deliveredBtn', 'Delivered']]) {
  const response = page.waitForResponse(value => value.url().includes('/api/orders/update-status') && value.request().method() === 'POST');
  const [result] = await Promise.all([response, page.locator('#' + button).click()]); assert.equal(result.status(), 200);
  await page.waitForFunction(value => document.querySelector('#orderSummaryDiv')?.textContent.includes(value), status);
 }
 pass('Staff order transitions Pending → Confirmed → Shipped → Delivered persist and refresh');
 await page.goto(baseURL + '/page/Staff/orderinfo.html?OrderId=2', { waitUntil: 'domcontentloaded' });
 for (const [button, status] of [['shippedBtn', 'Shipped'], ['deliveredBtn', 'Delivered']]) {
  const response = page.waitForResponse(value => value.url().includes('/api/orders/update-status') && value.request().method() === 'POST');
  const [result] = await Promise.all([response, page.locator('#' + button).click()]); assert.equal(result.status(), 200);
  await page.waitForFunction(value => document.querySelector('#orderSummaryDiv')?.textContent.includes(value), status);
 }
 pass('Existing Processing orders can progress to Shipped and Delivered without rewriting old records');
 // All historical order references remain protected, even after fulfillment.
 assert.equal((await api('delete', { id: 1 })).status, 409);
 const report = page.waitForResponse(response => response.url().includes('genReport.php'));
 await page.goto(baseURL + '/page/Staff/genreport.html', { waitUntil: 'domcontentloaded' }); assert.equal((await report).status(), 200);
 const download = page.waitForEvent('download'); await page.getByRole('button', { name: /CSV/ }).click(); const file = await download; assert.ok(file.suggestedFilename().endsWith('.csv')); assert.equal(await file.failure(), null); pass('Stock report loads and CSV export downloads successfully');
 const dealer = await browser.newPage();
 await dealer.goto(baseURL, { waitUntil: 'domcontentloaded' });
 await dealer.locator('#username').fill(demo.users.Dealer); await dealer.locator('#password').fill(demo.password); await dealer.locator('#loginButton').click(); await dealer.waitForURL('**/page/Dealer/viewSparePart.php');
 const forbidden = await dealer.evaluate(async () => { const response = await fetch('/resources/php/ManageItem.php?action=delete', { method: 'POST', headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + window.SLMC_AUTH.getToken() }, body: JSON.stringify({ id: 1 }) }); return response.status; });
 assert.equal(forbidden, 403); pass('Dealer accounts cannot bypass staff deletion authority');
 assert.deepEqual(errors, []); pass('No uncaught browser errors during tested staff workflows');
} finally {
 await writeFile(root + '/docs/testing/staff-workflow-results.json', JSON.stringify({ capturedAt: new Date().toISOString(), project: 'asp-staff-review', passed }, null, 2) + '\n');
 await browser.close();
}
