---
paths:
  - 'tests/Feature/**'
---

# Feature

## Feature tests can't replay flashed validation errors across requests
Feature tests do not carry the session cookie, so each request gets a new session id and flashed `errors`/`_old_input` do not reliably survive PUT->GET. `withSession(['errors' => ...])` also gets reset before render. To assert that a page shows `@error`/`is-invalid` blocks, do: PUT -> `assertSessionHasErrors([...])` (proves the validator keys), then GET the page and render the returned `$response->original` View with `->withErrors([...])->render()` and assert the HTML toContain the messages. (ProfileController / UpdateProfilRequest)
