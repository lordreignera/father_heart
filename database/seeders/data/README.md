# Uganda location seed data

`uganda_locations.json` is a compact, reproducible extract of the district/city and
subcounty/town-council/division levels from [uganda-locale](https://github.com/kakandemanwell/uganda).
Its underlying administrative records are compiled from Uganda government and
humanitarian sources. Dataset attribution and licence: [CC BY 4.0](https://github.com/kakandemanwell/uganda/blob/master/LICENSE-DATA.md).

Regenerate with `node scripts/build-uganda-locations.mjs`. The source release used
here reports 146 district-equivalent units and 2,191 subcounty-tier units; compare
against [UBOS NPHC 2024](https://www.ubos.org/nphc-2024-census-page/) when updating.
The seed retains source IDs and UBOS codes where supplied. It does not rewrite
names that staff have edited in the application.
