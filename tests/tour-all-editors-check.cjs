const fs=require('node:fs'),assert=require('node:assert/strict');
const {chromium}=require(process.env.TOURISM_PLAYWRIGHT||'playwright-core');
const state=JSON.parse(fs.readFileSync(process.env.TOURISM_EDITOR_STATE,'utf8'));
async function main(){
 const browser=await chromium.launch({executablePath:process.env.TOURISM_CHROME||'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 try{
  const context=await browser.newContext();await context.addCookies(state.cookies);
  const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));page.on('dialog',d=>d.accept());
  await page.goto(`http://travel-theme.local/wp-admin/post.php?post=${state.tourId}&action=edit`,{waitUntil:'networkidle'});
  async function checkEditors(){
   const editors=await page.locator('.tourism-editor-panel .wp-editor-wrap').all();
   for(const editor of editors){
    assert.equal(await editor.locator('.wp-switch-editor').count(),2);
    assert.equal(await editor.locator('.insert-media').count(),1);
    const id=await editor.locator('textarea').getAttribute('id');
    await page.waitForFunction(id=>tinymce.get(id)?.initialized,id);
    assert(await page.evaluate(id=>tinymce.get(id).settings.toolbar1.includes('alignleft'),id), 'Incomplete toolbar: '+id);
   }
   console.log('PASS: Full toolbar, media and unique tabs in '+editors.length+' editors.');
  }
  await checkEditors();
  for(const spec of [
   {tab:'itinerary',add:'tourism-core-add-itinerary-item',row:'.tourism-core-itinerary-item',del:'.tourism-core-delete-itinerary'},
   {tab:'faqs',add:'tourism-core-add-faq',row:'.tourism-core-faq-item',del:'.tourism-core-delete-faq'},
   {tab:'pricing',add:'tourism-core-add-pricing-info',row:'.tourism-core-pricing-info-item',del:'.tourism-core-delete-pricing-info'}
  ]){
   await page.locator('#tourism-tab-'+spec.tab).click();
   await page.locator('#'+spec.add).click();await checkEditors();
   const row=page.locator(spec.row).last();const id=await row.locator('textarea').getAttribute('id');
   await row.locator('.insert-media').click();
   await page.locator('.media-modal:visible').waitFor();
   assert.equal(await page.evaluate(()=>wp.media.editor.activeEditor),id);
   await page.locator('.media-modal:visible .media-modal-close').click();
   await row.locator(spec.del).click();await checkEditors();
   console.log('PASS: Dynamic editor, media target and removal in '+spec.tab);
  }
  assert.deepEqual(errors,[]);console.log('All rich-text editor checks passed.');
 }finally{await browser.close();}
}
main().catch(e=>{console.error(e);process.exitCode=1});
