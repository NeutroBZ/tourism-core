/* Experience editor lifecycle, native media insertion, and rich-text persistence. */
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require(process.env.TOURISM_PLAYWRIGHT || 'playwright-core');
const state = JSON.parse(fs.readFileSync(process.env.TOURISM_EDITOR_STATE, 'utf8'));
const url = `${state.baseUrl || 'http://travel-theme.local'}/wp-admin/post.php?post=${state.tourId}&action=edit`;
const check = (condition, label) => { assert(condition, label); console.log('PASS: ' + label); };
const groups = [
 { container: 'tourism-core-highlights-container', add: 'tourism-core-add-highlight', del: 'tourism-core-delete-highlight', key: 'tourism_highlights' },
 { container: 'tourism-core-what-to-expect-container', add: 'tourism-core-add-what-to-expect', del: 'tourism-core-delete-what-to-expect', key: 'tourism_what_to_expect' },
 { container: 'tourism-includes-container', add: 'tourism-add-include', del: 'tourism-delete-include', key: 'tourism_includes_new' },
 { container: 'tourism-excludes-container', add: 'tourism-add-exclude', del: 'tourism-delete-exclude', key: 'tourism_excludes_new' },
];
async function main() {
 const browser = await chromium.launch({ executablePath: process.env.TOURISM_CHROME || 'C:/Program Files/Google/Chrome/Application/chrome.exe', headless: true });
 try {
  const context = await browser.newContext({ viewport: { width: 1440, height: 1050 } });
  await context.addCookies(state.cookies);
  const page = await context.newPage();
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  await page.goto(url, { waitUntil: 'networkidle' });
  await page.locator('#tourism-tab-experience').click();
  for (const group of groups) {
   const container = page.locator('#' + group.container);
   const rows = container.locator('.tourism-experience-item');
   await container.evaluate(node => node.previousElementSibling.querySelector('button').click());
   const editorId = await rows.first().locator('textarea').getAttribute('id');
   await page.waitForFunction(id => window.tinymce?.get(id)?.initialized, editorId);
   for (const row of await rows.all()) {
    check(await row.locator('.wp-editor-wrap').count() === 1 && await row.locator('.wp-switch-editor.switch-tmce').count() === 1 && await row.locator('.wp-switch-editor.switch-html').count() === 1, 'One Visual/Code pair in saved row: ' + group.key);
    check(await row.locator('.insert-media').count() === 1, 'Native Add Media button in saved row: ' + group.key);
   }
   check(await page.evaluate(id => tinymce.get(id).settings.toolbar1.includes('alignleft') && tinymce.get(id).settings.toolbar1.includes('wp_adv'), editorId), 'Full WordPress toolbar: ' + group.key);

   // Use the real WordPress media modal and insert into the second editor.
   await rows.nth(1).locator('.insert-media').click();
   const modal = page.locator('.media-modal:visible');
   const libraryTab = modal.locator('.media-router .media-menu-item').filter({ hasText: /Media Library|Biblioteca de medios/ });
   if (await libraryTab.count()) await libraryTab.click();
   const attachment = modal.locator('.attachment[data-id="' + state.attachmentId + '"]');
   await attachment.waitFor({ state: 'visible' });
   await attachment.click();
   await modal.locator('.media-button-insert').click();
   await modal.waitFor({ state: 'hidden' });
   const secondId = await rows.nth(1).locator('textarea').getAttribute('id');
   await page.waitForFunction(id => tinymce.get(id).getContent().includes('<img'), secondId, { timeout: 5000 });
   check(await page.evaluate(id => tinymce.get(id).getContent().includes('<img'), secondId), 'Media inserted in the chosen editor: ' + group.key);
   await page.evaluate(id => {
    const editor = tinymce.get(id);
    editor.setContent(editor.getContent().replace('<img ', '<img class="tourism-media-check" '));
   }, secondId);

   await page.locator('#' + group.add).click();
   check(await rows.count() === 3, 'Adding creates exactly one row: ' + group.key);
   const added = rows.last();
   await added.locator('.tourism-experience-title').fill('Nuevo ' + group.key);
   const addedId = await added.locator('textarea').getAttribute('id');
   await page.waitForFunction(id => tinymce.get(id)?.initialized, addedId);
   check(await added.locator('.wp-switch-editor').count() === 2 && await added.locator('.insert-media').count() === 1, 'New row has one editor and Add Media: ' + group.key);
   await added.locator('.switch-html').click();
   await added.locator('textarea').fill('<p>Texto en código ' + group.key + '</p>');
   await rows.first().locator('.' + group.del).click();
   check(await rows.count() === 2, 'Deleting renumbers remaining rows: ' + group.key);
   await page.waitForFunction(id => tinymce.get(id)?.initialized, editorId);
   await rows.locator('.tourism-repeat-toggle').evaluateAll(buttons => buttons.forEach(button => { if (button.getAttribute('aria-expanded') === 'true') button.click(); }));
   await container.scrollIntoViewIfNeeded();
   const source = await rows.last().locator('.tourism-core-handle').boundingBox();
   const target = await rows.first().locator('.tourism-core-handle').boundingBox();
   await page.mouse.move(source.x + source.width / 2, source.y + source.height / 2);
   await page.mouse.down();
   await page.mouse.move(target.x + target.width / 2, target.y + target.height / 2 - 10, { steps: 15 });
   await page.mouse.up();
   await page.waitForFunction(({ id, title }) => document.querySelector('#' + id + ' .tourism-experience-title').value === title, { id: group.container, title: 'Nuevo ' + group.key });
   check(await rows.first().locator('.tourism-experience-title').inputValue() === 'Nuevo ' + group.key, 'Dragging changes row order: ' + group.key);
   for (const row of await rows.all()) check(await row.locator('.wp-editor-wrap').count() === 1 && await row.locator('.wp-switch-editor').count() === 2 && await row.locator('.insert-media').count() === 1, 'Editor controls stay unique after delete and drag: ' + group.key);
  }
  await page.locator('#tourism-tab-experience').click();
  await page.locator('#tourism-core-highlights-container').evaluate(node => node.previousElementSibling.querySelector('button').click());
  if (process.env.TOURISM_EDITOR_SCREENSHOTS) {
   fs.mkdirSync(process.env.TOURISM_EDITOR_SCREENSHOTS, { recursive: true });
   await page.screenshot({ path: path.join(process.env.TOURISM_EDITOR_SCREENSHOTS, 'experience-full-editors-desktop.png'), fullPage: true });
  }
  await page.setViewportSize({ width: 390, height: 844 });
  check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Full Experience editors fit mobile width');
  await page.setViewportSize({ width: 1440, height: 1050 });
  await page.locator('#tourism-tab-general').click();
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.locator('#save-post').click()]);
  await page.locator('#tourism-tab-experience').click();
  for (const group of groups) {
   const rows = page.locator('#' + group.container + ' .tourism-experience-item');
   check(await rows.count() === 2 && await rows.first().locator('.tourism-experience-title').inputValue() === 'Nuevo ' + group.key, 'Content and order persist after save: ' + group.key);
   for (const row of await rows.all()) check(await row.locator('.wp-switch-editor').count() === 2, 'No duplicate tabs after reload: ' + group.key);
  }
  check(errors.length === 0, 'No JavaScript errors: ' + errors.join('; '));
  console.log('All Experience editor browser checks passed.');
 } finally { await browser.close(); }
}
main().catch(error => { console.error(error); process.exitCode = 1; });
