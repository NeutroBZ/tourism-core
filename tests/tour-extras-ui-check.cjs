/* Local browser regression: add, edit, remove and save videos/reviews in the tour sections. */
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require(process.env.TOURISM_PLAYWRIGHT || 'playwright-core');
const state = JSON.parse(fs.readFileSync(process.env.TOURISM_EDITOR_STATE, 'utf8'));
const url = `${state.baseUrl}/wp-admin/post.php?post=${state.tourId}&action=edit`;
const check = (ok, message) => { assert(ok, message); console.log('PASS: ' + message); };
async function main() {
 const browser = await chromium.launch({ executablePath: process.env.TOURISM_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
 try {
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, ignoreHTTPSErrors: true });
  await context.addCookies(state.cookies);
  const page = await context.newPage(); const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.waitForSelector('.tourism-editor-active');
  check(await page.locator('.tourism-editor-tabs [role="tab"]:visible').count() === 8, 'Eight editor sections');
  check(await page.locator('#tourism-tour-extras').evaluate(node => node.closest('[data-section]')?.dataset.section) === 'extras', 'Metabox is inside its section');
  check(await page.locator('#tourism-tour-extras').isHidden(), 'Extras stay hidden while another section is selected');
  await page.locator('#tourism-tab-extras').click();
  const group = key => page.locator(`.tourism-extras-group[data-key="${key}"]`);
  const rows = key => group(key).locator('.tourism-extras-row:visible');
  async function addVideo(title, youtube, location = 'sidebar') {
   await group('tourism_videos').locator('.tourism-extras-add').click();
   const row = rows('tourism_videos').last();
   await row.locator('[name$="[title]"]').fill(title);
   await row.locator('[name$="[url]"]').fill(youtube);
   await row.locator('[name$="[location]"]').selectOption(location);
  }
  async function addReview(author, content, approve = false) {
   await group('tourism_review_items').locator('.tourism-extras-add').click();
   const row = rows('tourism_review_items').last();
   await row.locator('[name$="[author]"]').fill(author);
   await row.locator('[name$="[rating]"]').fill('4.5');
   await row.locator('[name$="[content]"]').fill(content);
   if (approve) await row.locator('[name$="[approved]"]').check();
  }
  async function save() {
   await page.locator('#tourism-tab-general').click();
   await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('#save-post').click()]);
   await page.waitForSelector('.tourism-editor-active');
   await page.locator('#tourism-tab-extras').click();
  }
  check(await rows('tourism_videos').count() === 0 && await rows('tourism_review_items').count() === 0, 'Empty lists start with add buttons');
  await addVideo('First video', 'https://youtu.be/dQw4w9WgXcQ');
  await addVideo('Second video', 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'main');
  await addReview('First author', 'First review', true);
  await addReview('Second author', 'Second review', true);
  await rows('tourism_videos').first().locator('.tourism-extras-remove').click();
  check(await rows('tourism_videos').count() === 1, 'Video removed immediately');
  const screenshotDir = process.env.TOURISM_EDITOR_SCREENSHOTS;
  if (screenshotDir) {
   fs.mkdirSync(screenshotDir, { recursive: true });
   await page.locator('#tourism-tour-extras').screenshot({ path: path.join(screenshotDir, 'videos-reviews-desktop.png') });
   await page.setViewportSize({ width: 390, height: 844 });
   check(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), 'No horizontal overflow on mobile');
   await page.locator('#tourism-tour-extras').screenshot({ path: path.join(screenshotDir, 'videos-reviews-mobile.png') });
   await page.setViewportSize({ width: 1440, height: 1000 });
  }
  await save();
  check(await rows('tourism_videos').count() === 1 && await rows('tourism_review_items').count() === 2, 'Added items and deletion persist after saving from another section');
  check(await rows('tourism_videos').first().locator('[name$="[title]"]').inputValue() === 'Second video', 'Remaining video retains its title');
  check(await rows('tourism_videos').first().locator('[name$="[location]"]').inputValue() === 'main', 'Video location persists');
  await rows('tourism_review_items').first().locator('.tourism-extras-remove').click();
  await rows('tourism_review_items').first().locator('[name$="[content]"]').fill('Second review edited');
  await addReview('Third author', 'New review awaiting approval');
  await addVideo('Third video', 'https://youtu.be/dQw4w9WgXcQ?t=30');
  await rows('tourism_videos').first().locator('.tourism-extras-remove').click();
  await save();
  check(await rows('tourism_videos').first().locator('[name$="[title]"]').inputValue() === 'Third video', 'Adding and removing in the same edit preserves the new video');
  check(await rows('tourism_review_items').first().locator('[name$="[author]"]').inputValue() === 'Second author', 'Removing a review retains the correct remaining author');
  check(await rows('tourism_review_items').first().locator('[name$="[content]"]').inputValue() === 'Second review edited', 'Remaining review can be edited');
  check(await rows('tourism_review_items').first().locator('[name$="[approved]"]').isChecked(), 'Existing approval persists');
  check(!await rows('tourism_review_items').last().locator('[name$="[approved]"]').isChecked(), 'New review starts unapproved');
  for (const key of ['tourism_videos', 'tourism_review_items']) {
   while (await rows(key).count()) await rows(key).first().locator('.tourism-extras-remove').click();
  }
  await save();
  check(await rows('tourism_videos').count() === 0 && await rows('tourism_review_items').count() === 0, 'Deleting all videos and reviews persists');
  check(errors.length === 0, 'No browser JavaScript errors: ' + errors.join('; '));
  const fallback = await browser.newContext({ javaScriptEnabled: false, ignoreHTTPSErrors: true });
  await fallback.addCookies(state.cookies); const raw = await fallback.newPage();
  await raw.goto(url, { waitUntil: 'networkidle' });
  const rawBox = raw.locator('#tourism-tour-extras');
  check(await rawBox.isVisible() && await rawBox.locator('.tourism-extras-row').count() === 2, 'Without JavaScript the metabox has an editable blank row per group');
  check(await rawBox.locator('.tourism-extras-add:visible').count() === 0 && await rawBox.locator('.tourism-extras-remove-fallback:visible').count() === 2, 'Fallback shows deletion checkboxes instead of inactive buttons');
  await rawBox.locator('[name="tourism_videos[0][title]"]').fill('No-JS video');
  await rawBox.locator('[name="tourism_videos[0][url]"]').fill('https://youtu.be/dQw4w9WgXcQ');
  await Promise.all([raw.waitForNavigation({ waitUntil: 'networkidle' }), raw.locator('#save-post').click()]);
  check(await rawBox.locator('[name="tourism_videos[0][title]"]').inputValue() === 'No-JS video', 'Video can be added without JavaScript');
  await rawBox.locator('[name="tourism_videos[0][remove]"]').check();
  await Promise.all([raw.waitForNavigation({ waitUntil: 'networkidle' }), raw.locator('#save-post').click()]);
  check(await rawBox.locator('[name="tourism_videos[0][title]"]').inputValue() === '', 'Video can be deleted without JavaScript');
  console.log('PASS: Tour videos/reviews browser checks completed.');
 } finally { await browser.close(); }
}
main().catch(error => { console.error(error); process.exitCode = 1; });
