---
paths:
  - 'app/Support/**'
---

# Support

## VerifikasiAntrean is the single source of the verification queue
Filters and ordering live only in `VerifikasiAntrean::antrean()`, and the Excel exports read from it too -- never re-implement the stage rules in a query or an export class, or the list and the download will drift. Careful: `Collection::sortBy()` takes ONE key. Passing an array of closures silently sorts descending on the first and drops the rest; chain `->sortBy(nama)->sortBy(rank)` instead, `sortBy` being stable.
