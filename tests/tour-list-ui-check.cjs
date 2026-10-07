/* First export TOURISM_LIST_PREVIEW with wordpress-tour-list-test.php. */
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const { chromium } = require(process.env.TOURISM_PLAYWRIGHT || 'playwright');

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.TOURISM_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
  try {
    const page = await browser.newPage();
    await page.route('https://example.invalid/**', route => route.abort());
    await page.setViewportSize({ width: 1200, height: 900 });
    await page.setContent(fs.readFileSync(process.env.TOURISM_LIST_PREVIEW, 'utf8'));
    const columnCount = () => page.locator('.tourism-core-list').evaluate(element => getComputedStyle(element).gridTemplateColumns.split(' ').length);
    if (await columnCount() !== 3) throw new Error('Desktop columns incorrect');
    await page.screenshot({ path: path.join(os.tmpdir(), 'tourism-list-desktop.png') });
    await page.setViewportSize({ width: 390, height: 844 });
    if (await columnCount() !== 1) throw new Error('Mobile columns incorrect');
    if (await page.evaluate(() => document.documentElement.scrollWidth > innerWidth)) throw new Error('Mobile overflow');
    await page.screenshot({ path: path.join(os.tmpdir(), 'tourism-list-mobile.png') });
    console.log('PASS: desktop grid, mobile grid and no horizontal overflow');
  } finally {
    await browser.close();
  }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
