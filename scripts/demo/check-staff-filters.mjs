import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
const root=fileURLToPath(new URL('../../',import.meta.url));
const demo=JSON.parse(execFileSync('php',['-r','require "resources/php/class/DemoAccounts.php"; echo json_encode([DemoAccounts::USERS["Sales manager"],DemoAccounts::PASSWORD]);'],{cwd:root,encoding:'utf8'}));
const b=await chromium.launch({channel:'chrome',headless:true});const base=process.env.DEMO_BASE_URL||'http://127.0.0.1:8880';
try {
 const p=await b.newPage({viewport:{width:1440,height:1000}});const errors=[];p.on('pageerror',e=>errors.push(e.message));p.setDefaultTimeout(12000);
 await p.goto(base,{waitUntil:'domcontentloaded'});await p.locator('#username').fill(demo[0]);await p.locator('#password').fill(demo[1]);await p.locator('#loginButton').click();await p.waitForURL('**/page/Staff/order.html');
 for(const [file,body]of [['Item.html','sparePartsTableBody'],['order.html','orderTableBody'],['genreport.html','reportTableBody']]){
  await p.goto(base+'/page/Staff/'+file,{waitUntil:'domcontentloaded'});await p.waitForFunction(id=>document.getElementById(id)?.querySelector('tr[data-search]'),body);
  const all=await p.locator('#'+body+' tr[data-search]').count();assert.ok(all>0);
  async function check(predicate){const result=await p.evaluate(({id,source})=>{const rows=[...document.getElementById(id).rows].filter(r=>r.dataset.search!==undefined);return rows.every(row=>row.hidden!==Function('d','return '+source)(row.dataset));},{id:body,source:predicate});assert.equal(result,true,file+' filter predicate');}
  if(file==='Item.html'){
   await p.locator('#checkbox-table-1').check();await p.locator('#staff-filter-search').fill('zz-no-such-product');assert.equal(await p.locator('#checkbox-table-1').isChecked(),false);assert.equal(await p.locator('.staff-filter-empty').isVisible(),true);
   await p.getByRole('button',{name:'Clear filters'}).click();await p.locator('#staff-filter-category').selectOption('1');await p.locator('#staff-filter-stock').selectOption('available');await p.locator('#staff-filter-locked').selectOption('true');await check('d.category === "1" && Number(d.stock)>0 && d.locked === "true"');
   await p.locator('#checkbox-all').check();
   assert.equal(await p.locator('#'+body+' tr[hidden] input:checked').count(), 0);
   assert.equal(await p.locator('#'+body+' tr:not([hidden]) input:not(:checked)').count(), 0);
   await p.locator('#checkbox-all').uncheck();
   await p.locator('#staff-filter-search').fill('  VEHICLE  ');await check('d.category === "1" && Number(d.stock)>0 && d.locked === "true" && d.search.toLowerCase().includes("vehicle")');
   await p.screenshot({path:root+'/docs/screenshots/staff-catalog-filtered.jpg',fullPage:true,type:'jpeg',quality:80,animations:'disabled'});
  }else if(file==='order.html'){
   await p.locator('#staff-filter-status').selectOption('Shipped');await check('d.status === "Shipped"');
   await p.locator('#staff-filter-from').fill('2024-01-01');await p.locator('#staff-filter-to').fill('2024-12-31');await check('d.status === "Shipped" && d.date >= "2024-01-01" && d.date <= "2024-12-31"');
   await p.locator('#staff-filter-from').fill('2025-01-01');assert.match(await p.locator('.staff-filter-count').innerText(),/end date/);
  }else{
   await p.locator('#staff-filter-sales').selectOption('sold');await check('Number(d.sales)>0');await p.locator('#staff-filter-search').fill('headlight');await check('Number(d.sales)>0 && d.search.toLowerCase().includes("headlight")');
   const visible=await p.locator('#'+body+' tr[data-search]:not([hidden])').count();const waiting=p.waitForEvent('download');await p.getByRole('button',{name:/CSV/}).click();const download=await waiting;const stream=await download.createReadStream();let csv='';for await(const chunk of stream)csv+=chunk;assert.equal(csv.trim().split('\n').length,visible+1);
  }
  await p.getByRole('button',{name:'Clear filters'}).click();assert.equal(await p.locator('#'+body+' tr[data-search]:not([hidden])').count(),all);
  await p.setViewportSize({width:390,height:844});assert.ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));await p.setViewportSize({width:1440,height:1000});
  console.log('PASS search, combined filters, reset and mobile: '+file);
 }
 assert.deepEqual(errors,[]);console.log('PASS filtered CSV and no uncaught browser errors.');
}finally{await b.close();}
