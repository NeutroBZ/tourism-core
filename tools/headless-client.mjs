/** Server/build use only. Never import this module into a browser component. */
import { readFile } from 'node:fs/promises';

export async function loadHeadlessSnapshot(env = process.env) {
  let data;
  if (env.WP_SNAPSHOT_FILE) {
    data = JSON.parse(await readFile(env.WP_SNAPSHOT_FILE, 'utf8'));
  } else {
    if (!env.WP_BACKEND_URL || !env.WP_BUILD_USER || !env.WP_BUILD_APP_PASSWORD) {
      throw new Error('Missing private WordPress build configuration.');
    }
    const base = new URL(env.WP_BACKEND_URL);
    if (base.protocol !== 'https:') throw new Error('Authenticated WordPress access requires HTTPS.');
    if (base.username || base.password || base.search || base.hash) throw new Error('Use a backend URL without embedded credentials, query or fragment.');
    const endpoint = new URL(base.href.replace(/\/$/, '') + '/wp-json/tourism/v1/headless');
    const response = await fetch(endpoint, {
      headers: { Authorization: 'Basic ' + Buffer.from(env.WP_BUILD_USER + ':' + env.WP_BUILD_APP_PASSWORD).toString('base64') },
      redirect: 'error',
      signal: AbortSignal.timeout(180000)
    });
    if (!response.ok) throw new Error('WordPress build export failed: HTTP ' + response.status);
    data = await response.json();
  }
  if (data.contract_version !== 1 || !Array.isArray(data.posts) || !Array.isArray(data.routes)) {
    throw new Error('Unsupported WordPress data contract.');
  }
  if (env.FRONTEND_ORIGIN && new URL(env.FRONTEND_ORIGIN).origin !== data.frontend_origin) {
    throw new Error('Frontend origin does not match the exported canonical URLs.');
  }
  const seen = new Set();
  for (const route of data.routes) {
    if (seen.has(route.path)) throw new Error('Duplicate frontend route: ' + route.path);
    seen.add(route.path);
  }
  return data;
}
