/* Offline gate for Astro inputs. Does not request or modify the production site. */
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const input = process.argv[2];
if (!input) throw Error('Usage: node validate-headless-links.cjs snapshot.json [output-directory]');
const data = JSON.parse(fs.readFileSync(input, 'utf8'));
const output = process.argv[3] || path.dirname(input);
const paths = new Map(data.routes.map(r => [r.path, r]));
const redirects = new Map(data.redirects.map(r => [r.from, r]));
const assets = new Set((data.assets || []).map(a => a.path));
const failures = [], links = [], components = new Map();
const decode = s => s.replaceAll('&amp;', '&').replaceAll('&#038;', '&').replaceAll('&#039;', "'").replaceAll('&quot;', '"');
function resolve(url) {
  let u; try { u = new URL(decode(url), data.frontend_origin); } catch { return { error: 'invalid_url' }; }
  if (!['http:', 'https:'].includes(u.protocol) || u.origin !== data.frontend_origin) return { external: true };
  if (u.searchParams.has('s')) return { query: 'search' };
  for (const key of ['p', 'page_id', 'attachment_id']) {
    const id = u.searchParams.get(key);
    if (!id) continue;
    const to = data.legacy_post_ids[id] || data.backend_post_ids[id] || data.legacy_media_ids?.[id];
    return to ? resolve(to) : { error: 'unknown_query_id', id };
  }
  const blogPage = Number(u.searchParams.get('blog_page'));
  if (blogPage > 1) u.pathname = u.pathname.replace(/\/?$/, '/') + 'page/' + blogPage + '/';
  let p = u.pathname; const visited = new Set();
  while (redirects.has(p)) {
    if (visited.has(p)) return { error: 'redirect_cycle', path: p };
    visited.add(p); p = new URL(redirects.get(p).to, data.frontend_origin).pathname;
  }
  if (paths.has(p)) return { path: p, redirects: visited.size };
  if (paths.has(p + '/')) return { path: p + '/', slashNormalization: true, redirects: visited.size };
  if (p.startsWith('/wp-content/uploads/')) return assets.has(decodeURIComponent(p)) ? { asset: p } : { error: 'missing_asset', path: p };
  return { error: 'missing_route', path: p, query: u.search };
}
function link(from, url) {
  if (!url || url === '#' || /^(?:mailto:|tel:|javascript:)/i.test(url)) return;
  const result = resolve(url); links.push({ from, url, ...result });
  if (result.error) failures.push({ from, url, ...result });
}
for (const p of data.posts) {
  const seoLinks = value => {
    if (typeof value === 'string' && /^https?:\/\/[^\s<>]+$/.test(value)) link('seo:' + p.path, value);
    else if (value && typeof value === 'object') Object.values(value).forEach(seoLinks);
  };
  seoLinks(p.seo); seoLinks(p.translations); seoLinks(p.tour_schema); seoLinks(p.article_schema);
  for (const match of p.content.html.matchAll(/\bhref\s*=\s*["']([^"']+)["']/gi)) link(p.path, match[1]);
  for (const value of Object.values(p.fields)) {
    const inspect = v => {
      if (typeof v === 'string') for (const m of v.matchAll(/\bhref\s*=\s*["']([^"']+)["']/gi)) link(p.path, m[1]);
      else if (v && typeof v === 'object') Object.values(v).forEach(inspect);
    }; inspect(value);
  }
  for (const s of p.content.segments) if (s.type === 'component') components.set(s.name, (components.get(s.name) || 0) + 1);
}
for (const menu of data.menus) {
  const ids = new Set(menu.items.map(i => i.id));
  for (const item of menu.items) {
    if (item.parent_id && !ids.has(item.parent_id)) failures.push({ menu: menu.name, item: item.id, error: 'missing_menu_parent' });
    link('menu:' + menu.name, item.url);
  }
}
for (const r of data.redirects) link('redirect:' + r.from, r.to);
const assetRefs = new Set();
function assetRef(value) {
  try {
    const u = new URL(decode(value));
    if (u.origin === data.frontend_origin && u.pathname.startsWith('/wp-content/uploads/')) assetRefs.add(decodeURIComponent(u.pathname));
  } catch {}
}
function walk(value) {
  if (typeof value === 'string') {
    if (/^https?:\/\/[^\s<>]+$/.test(value)) assetRef(value);
    for (const m of value.matchAll(/\b(?:src|poster|href)\s*=\s*["']([^"']+)["']/gi)) assetRef(m[1]);
    for (const m of value.matchAll(/\bsrcset\s*=\s*["']([^"']+)["']/gi)) for (const part of m[1].split(',')) assetRef(part.trim().split(/\s+/)[0]);
  } else if (value && typeof value === 'object') Object.values(value).forEach(walk);
}
walk(data.posts); walk(data.site); walk(data.media); walk(data.terms);
for (const asset of assetRefs) if (!assets.has(asset)) failures.push({ error: 'missing_asset', path: asset });
const blocked = failures.filter(f => f.error === 'redirect_cycle');
const notes = [
  'Component templates, forms, search, feeds and archive routes must be implemented and crawled in Astro before release.',
  'Live production sitemap and Search Console URL coverage have not been verified by this offline audit.',
  'All old upload URLs require a media copy or a verified proxy to the permanent HTTPS backend.'
];
const report = {
  result: failures.length ? 'FAIL' : 'PASS',
  snapshot_sha256: crypto.createHash('sha256').update(fs.readFileSync(input)).digest('hex'),
  content: data.posts.length, routes: paths.size, redirects: redirects.size,
  preservedAssetPaths: assets.size, referencedAssets: assetRefs.size,
  links: links.length, failures, redirectCycles: blocked.length,
  components: Object.fromEntries(components), notes
};
fs.mkdirSync(output, { recursive: true });
fs.writeFileSync(path.join(output, 'link-validation.json'), JSON.stringify(report, null, 2));
fs.writeFileSync(path.join(output, 'internal-links.json'), JSON.stringify(links, null, 2));
const csv = [['type', 'id', 'language', 'public_url', 'backend_url', 'canonical', 'seo_title'], ...data.posts.map(p => [p.type, p.id, p.language, p.url, p.backend_url, p.seo.canonical, p.seo.title])].map(row => row.map(v => '"' + String(v).replaceAll('"', '""') + '"').join(',')).join('\n');
fs.writeFileSync(path.join(output, 'url-map.csv'), '\ufeff' + csv);
fs.writeFileSync(path.join(output, 'netlify-redirects.pending'), '# Do not deploy until the Astro crawl and asset checks pass.\n' + data.redirects.map(r => `${r.from} ${r.to} ${r.status}`).join('\n') + '\n');
console.log(JSON.stringify({ result: report.result, content: report.content, routes: report.routes, redirects: report.redirects, links: report.links, failures: failures.length, redirectCycles: blocked.length, preservedAssetPaths: assets.size, referencedAssets: assetRefs.size, components: report.components }));
if (failures.length) process.exitCode = 1;
