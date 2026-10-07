/* Regression checks for first paint and failed editor assets on the local fixture. */
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { chromium } = require(process.env.TOURISM_PLAYWRIGHT || 'playwright-core');
const state = JSON.parse(fs.readFileSync(process.env.TOURISM_EDITOR_STATE, 'utf8'));
const url = `http://travel-theme.local/wp-admin/post.php?post=${state.tourId}&action=edit`;
const check = (condition, label) => { assert(condition, label); console.log('PASS: ' + label); };
async function main() {
 const browser = await chromium.launch({ executablePath: process.env.TOURISM_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
 try {
  const context = await browser.newContext();
  await context.addCookies(state.cookies);
  await context.addInitScript(() => {
   window.oldTourFrames = 0;
   function sample() {
    const box = document.getElementById('tourism-core-tour-details');
    if (box && !document.body.classList.contains('tourism-editor-active') && getComputedStyle(box).visibility === 'visible') window.oldTourFrames++;
    requestAnimationFrame(sample);
   }
   requestAnimationFrame(sample);
  });
  const page = await context.newPage();
  let release;
  const delay = new Promise(resolve => { release = resolve; });
  await page.route('**/tour-editor.js?*', async route => { await delay; await route.continue(); });
  await page.goto(url, { waitUntil: 'commit' });
  await page.waitForSelector('#tourism-core-tour-details', { state: 'attached' });
  check(await page.locator('#tourism-editor-loading').isVisible(), 'Loading state visible while editor script is delayed');
  check(await page.locator('#tourism-core-tour-details').evaluate(node => getComputedStyle(node).visibility === 'hidden'), 'Original layout hidden before editor initialization');
  check(await page.locator('#submitdiv').isVisible(), 'Native WordPress publishing controls remain visible');
  check(await page.evaluate(() => window.oldTourFrames) === 0, 'No frame exposes the old layout before initialization');
  release();
  await page.waitForSelector('.tourism-editor-active');
  check(await page.locator('#tourism-editor-loading').isHidden(), 'Loading state ends when section layout is ready');
  check(await page.evaluate(() => window.oldTourFrames) === 0, 'No old-layout flash during transition to sections');

  const failed = await context.newPage();
  await failed.route('**/tour-editor.js?*', route => route.abort());
  await failed.goto(url, { waitUntil: 'networkidle' });
  check(await failed.locator('#tourism-core-tour-details').isVisible(), 'Failed editor asset restores the original form');
  check(await failed.locator('#tourism-editor-recovery').isVisible(), 'Failed asset displays a recovery explanation');
  check(await failed.locator('#tourism-editor-loading').isHidden(), 'Failed asset cannot leave an endless loading state');

  const late = await context.newPage();
  let finishLate;
  const stalled = new Promise(resolve => { finishLate = resolve; });
  await late.route('**/tour-editor.js?*', async route => { await stalled; await route.continue(); });
  await late.goto(url, { waitUntil: 'commit' });
  await late.waitForSelector('#tourism-core-tour-details', { state: 'attached' });
  // Trigger the actual watchdog callback without waiting ten seconds in this test.
  await late.evaluate(() => TourismCoreEditorBoot.fallback());
  finishLate();
  await late.waitForLoadState('networkidle');
  check(await late.locator('#tourism-editor-recovery').isVisible(), 'Stalled initialization recovers the form');
  check(await late.locator('.tourism-editor-active').count() === 0, 'Late asset does not switch layout after recovery');

  const noJs = await browser.newContext({ javaScriptEnabled: false });
  await noJs.addCookies(state.cookies);
  const raw = await noJs.newPage();
  await raw.goto(url);
  check(await raw.locator('#tourism-core-tour-details').isVisible(), 'No-JavaScript form remains accessible');
  check(await raw.locator('#tourism-editor-loading').isHidden(), 'No-JavaScript form does not show a loading placeholder');
  console.log('All editor loading checks passed.');
 } finally { await browser.close(); }
}
main().catch(error => { console.error(error); process.exitCode = 1; });
