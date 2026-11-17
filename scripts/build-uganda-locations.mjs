import { mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

// Rebuild the checked-in, compact seed fixture from the attributed public dataset.
const base = 'https://data.mnwll.tech/data/';
const names = ['districts', 'citys', 'countys', 'subcountys', 'town_councils', 'divisions'];
const entries = await Promise.all(names.map(async (name) => {
  const response = await fetch(`${base}${name}.json`);
  if (!response.ok) throw new Error(`Could not fetch ${name}: HTTP ${response.status}`);
  return [name, await response.json()];
}));
const data = Object.fromEntries(entries);
const units = new Map(entries.flatMap(([, rows]) => rows.map((row) => [row.id, row])));
const districts = [...data.districts, ...data.citys].map((row) => ({
  source_id: row.id,
  name: row.name,
  kind: row.type,
  ubos_code: row.external_refs?.ubos_code ?? null,
}));
const districtIds = new Set(districts.map((row) => row.source_id));
const subcounties = [...data.subcountys, ...data.town_councils, ...data.divisions].map((row) => {
  let parent = row.parent_id;
  while (parent && !districtIds.has(parent)) parent = units.get(parent)?.parent_id;
  if (!parent) throw new Error(`No district/city parent for ${row.id}`);
  return {
    source_id: row.id,
    district_source_id: parent,
    name: row.name,
    kind: row.type,
    ubos_code: row.external_refs?.ubos_code ?? null,
  };
});
if (districts.length !== 146 || subcounties.length !== 2191) {
  throw new Error(`Unexpected source coverage: ${districts.length} districts/cities, ${subcounties.length} subcounty-tier units`);
}
const target = resolve(dirname(fileURLToPath(import.meta.url)), '../database/seeders/data/uganda_locations.json');
await mkdir(dirname(target), { recursive: true });
await writeFile(target, JSON.stringify({ districts, subcounties }) + '\n');
console.log(`Wrote ${districts.length} districts/cities and ${subcounties.length} subcounty-tier units.`);
