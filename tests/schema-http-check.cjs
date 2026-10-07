const assert = require('assert/strict');
(async () => {
	const tours = await fetch('http://travel-theme.local/wp-json/wp/v2/tour?per_page=1&_fields=id,link').then(r => r.json());
	assert.ok(Array.isArray(tours) && tours.length, 'At least one public tour is required');
	const response = await fetch(tours[0].link);
	assert.equal(response.status, 200);
	const html = await response.text();
	const scripts = [...html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)];
	const graphs = scripts.map(match => JSON.parse(match[1]));
	const toursInSchema = graphs.flatMap(graph => graph['@graph'] || [graph]).filter(node => [node['@type']].flat().includes('Product') && [node['@type']].flat().includes('TouristTrip'));
	assert.equal(toursInSchema.length, 1, 'Exactly one dual-typed tour entity should be emitted');
	assert.equal((html.match(/id="tourism-core-tour-schema"/g) || []).length, 1);
	assert.ok(toursInSchema[0].name && toursInSchema[0].url);
	console.log(JSON.stringify({ http: response.status, jsonLdBlocks: scripts.length, tourEntities: toursInSchema.length, types: toursInSchema[0]['@type'], hasOffer: !!toursInSchema[0].offers }));
})().catch(error => { console.error(error.message); process.exitCode = 1; });
