import { readFile, writeFile, access } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
const root = fileURLToPath(new URL('../../', import.meta.url));
const manifest = JSON.parse(await readFile(root + '/docs/screenshots/manifest.json', 'utf8'));
const groups = [
  ['Sign-in and information', manifest.pages.filter(page => ['login', 'login-dark', 'demo-accounts', 'privacy-policy', 'terms-of-use', 'qa', 'about'].includes(page.name))],
  ['Dealer pages', manifest.pages.filter(page => page.name.startsWith('dealer-'))],
  ['Staff pages', manifest.pages.filter(page => page.name.startsWith('staff-'))],
  ['Checkout states', [{ name: 'cart-populated', title: 'Order Summary with demo items' }, { name: 'checkout-loading', title: 'Placing the order' }, { name: 'checkout-confirmed', title: 'Confirmation and redirect' }]],
  ['Sign-out and error pages', manifest.pages.filter(page => page.name.startsWith('logout') || page.name.startsWith('error-'))],
];
let gallery = '## Demo gallery\n\nReal browser captures of the running legacy portal. Checkout captures use an\nisolated seeded database; its loading capture holds the real request in flight.\nError-page HTTP statuses are intentional. Svelte migration targets and historical\nprototypes are not part of the running portal.\n\n';
for (const [title, pages] of groups) {
  gallery += `<details>\n<summary>${title}</summary>\n\n`;
  for (const page of pages) {
    await access(root + `/docs/screenshots/${page.name}.jpg`);
    gallery += `**${page.title}**\n\n![${page.title}](docs/screenshots/${page.name}.jpg)\n\n`;
  }
  gallery += '</details>\n\n';
}
gallery += '[Capture inventory and HTTP results](docs/screenshots/manifest.json).\n';
const start = '<!-- DEMO_GALLERY_START -->'; const end = '<!-- DEMO_GALLERY_END -->';
const readmePath = root + '/README.md'; let readme = await readFile(readmePath, 'utf8');
const region = `${start}\n${gallery}\n${end}`;
if (readme.includes(start)) readme = readme.slice(0, readme.indexOf(start)) + region + readme.slice(readme.indexOf(end) + end.length);
else readme += '\n' + region + '\n';
await writeFile(readmePath, readme);
console.log(`README gallery generated with ${groups.reduce((count, [, pages]) => count + pages.length, 0)} screenshots.`);
