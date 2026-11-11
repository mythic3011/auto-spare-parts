import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import fs from 'node:fs/promises';
import assert from 'node:assert/strict';
const root=fileURLToPath(new URL('../../',import.meta.url));
const demo=JSON.parse(execFileSync('php',['-r','require "resources/php/class/DemoAccounts.php"; echo json_encode(["password"=>DemoAccounts::PASSWORD,"email"=>DemoAccounts::USERS["Sales manager"]]);'],{cwd:root,encoding:'utf8'}));
const base=process.env.DEMO_BASE_URL||'http://127.0.0.1:8880';
const browser=await chromium.launch({channel:'chrome',headless:true});
const results=[];
try{
 const context=await browser.newContext({viewport:{width:1440,height:1000}});const p=await context.newPage();p.setDefaultTimeout(12000);const errors=[];p.on('pageerror',e=>errors.push(e.stack));await p.goto(base,{waitUntil:'domcontentloaded'});await p.locator('#username').fill(demo.email);await p.locator('#password').fill(demo.password);await p.locator('#loginButton').click();await p.waitForURL('**/page/Staff/order.html');
 for(const [file,name] of [['order.html','staff-orders'],['Item.html','staff-items'],['orderinfo.html?OrderId=1','staff-order'],['addItem.html','staff-add-item'],['editItem.html?id=1','staff-edit-item'],['genreport.html','staff-report']]){
  await p.setViewportSize({width:1440,height:1000});await p.goto(base+'/page/Staff/'+file,{waitUntil:'domcontentloaded'});await p.waitForLoadState('networkidle',{timeout:5000}).catch(()=>{});
  assert.equal(await p.locator('main').count(),1);assert.equal(await p.locator('.staff-sidebar').count(),1);assert.equal(await p.locator('.staff-sidebar a[aria-current="page"]').count(),1);
  assert.ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));await p.screenshot({path:root+'/docs/screenshots/'+name+'.jpg',fullPage:true,type:'jpeg',quality:80,animations:'disabled'});
  await p.setViewportSize({width:390,height:844});assert.ok(await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),file+' mobile overflow');await p.locator('#mobileMenuButton').click();assert.equal(await p.locator('#mobileMenu').isVisible(),true);await p.keyboard.press('Escape');assert.equal(await p.locator('#mobileMenu').isVisible(),false);
  await p.screenshot({path:root+'/docs/screenshots/'+name+'-mobile.jpg',fullPage:true,type:'jpeg',quality:78,animations:'disabled'});
  await p.locator('#darkModeToggle').click();assert.equal(await p.evaluate(()=>document.documentElement.classList.contains('dark')),true);if(name==='staff-items')await p.screenshot({path:root+'/docs/screenshots/staff-items-dark.jpg',fullPage:true,type:'jpeg',quality:80,animations:'disabled'});await p.locator('#darkModeToggle').click();
  results.push(file);console.log('PASS desktop/mobile navigation, theme and overflow: '+file);
 }
 assert.deepEqual(errors,[]);console.log('No uncaught browser errors.');
}catch(e){console.error(e);throw e;}finally{await fs.writeFile(root+'/docs/testing/staff-layout-results.json',JSON.stringify({checkedAt:new Date().toISOString(),pages:results},null,2)+'\n');await browser.close();}
