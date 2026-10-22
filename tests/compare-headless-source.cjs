/* Compare the headless contract with the preserved RAW export, without loading old plugins. */
const fs = require('node:fs');
const path = require('node:path');
const [snapshotFile, rawDirectory] = process.argv.slice(2);
if (!snapshotFile || !rawDirectory) throw Error('Usage: node compare-headless-source.cjs snapshot.json raw-directory');
const snapshot = JSON.parse(fs.readFileSync(snapshotFile, 'utf8'));
const terms = new Map(snapshot.terms.map(t => [t.id, t]));
const taxonomies = new Set(['category', 'post_tag', 'post_format', 'tour_category', 'tour_tag']);
const failures = []; let checks = 0, relationships = 0;
const check = (ok, detail) => { checks++; if (!ok) failures.push(detail); };
for (const post of snapshot.posts) {
  const match = post.source_key.match(/^legacy-test-(tour|post|page)-(\d+)$/);
  if (!match) continue;
  const folder = {tour: 'tours', post: 'posts', page: 'pages'}[match[1]];
  const raw = JSON.parse(fs.readFileSync(path.join(rawDirectory, folder, match[2] + '.json'), 'utf8'));
  check(raw.post.post_title === post.title, {id: post.id, error: 'source_title_changed'});
  check(raw.post.post_date_gmt === post.published_gmt, {id: post.id, error: 'source_publication_date_changed'});
  check(raw.post.post_date === post.published_local, {id: post.id, error: 'source_local_date_changed'});
  const legacy = '/' + (post.language === 'fr' ? 'fr/' : '') + (post.type === 'tour' ? 'tour/' : '') + raw.post.post_name + '/';
  check(legacy === post.path || snapshot.route_aliases[legacy] === post.path, {id: post.id, error: 'original_route_not_covered', legacy, current: post.path});
  const original = raw.taxonomies.filter(t => taxonomies.has(t.taxonomy)).map(t => t.taxonomy + ':' + t.slug).sort();
  const current = [];
  for (const [taxonomy, ids] of Object.entries(post.terms)) if (taxonomies.has(taxonomy)) {
    for (const id of ids) current.push(taxonomy + ':' + terms.get(id)?.slug);
  }
  current.sort(); relationships += original.length;
  check(JSON.stringify(original) === JSON.stringify(current), {id: post.id, error: 'taxonomy_relationship_changed', original, current});
}
const report = {result: failures.length ? 'FAIL' : 'PASS', checks, posts: snapshot.posts.length, taxonomyRelationships: relationships, failures};
fs.writeFileSync(path.join(path.dirname(snapshotFile), 'source-comparison.json'), JSON.stringify(report, null, 2));
console.log(JSON.stringify({result: report.result, checks, posts: report.posts, taxonomyRelationships: relationships, failures: failures.length}));
if (failures.length) process.exitCode = 1;
