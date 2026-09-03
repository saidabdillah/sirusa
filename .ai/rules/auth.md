---
paths:
  - 'app/Http/Controllers/Auth/**'
---

# Auth

## New registrations default to non-active
AuthController::simpanDaftar creates users with status 'non-aktif' (they must be activated by an admin before they can log in). Login (simpanMasuk) rejects accounts where status !== 'aktif'. Always seed roles/permissions before tests that use Spatie roles, since RefreshDatabase wipes them.

## Login accepts email OR username
`StoreLoginRequest` exposes a single `login` field (`required|string|max:255`, NO `exists:users,email` rule so username works). `simpanMasuk` picks the credential column: `filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username'`, then `Auth::attempt([$field => $login, 'password' => ...])`. Error key and `withInput` use `login`. Login view `auth/masuk` uses `name="login"` type="text" labelled "Email atau Username". Both username and email columns are unique, so detection is unambiguous.
