---
paths:
  - 'app/Http/Requests/User/*.php'
  - 'app/Http/Requests/User/**'
---

# User

## Pengguna create/edit validate peran against the actor's assignableRoles()
`peran` is `required` + `Rule::in($this->user()->assignableRoles())` in BOTH Store and Update — never `Rule::exists('roles','name')` (roles can be created ad hoc via admin.role) and never `nullable` on edit (the `-- Pilih Peran --` placeholder sends `''`, which must fail, matching the verifikasi page). `assignableRoles()` drops `super_admin` unless the actor is one, and `peran.in` then reads 'Anda tidak dapat memberikan peran Super Admin'. `UpdateUserRequest::authorize()` also 403s a non-super_admin targeting a super_admin, before `rules()` run. NIM is deliberately absent: admins never set it, mahasiswa fill it in on the Profil page, so `StoreUserRequest` has no `nim` rule and `store()` creates the profile with `nik` only.

## Drop role-irrelevant input keys with remove(), never merge(null)
`StoreUserRequest::prepareForValidation()` drops `username`/`password`/`password_confirmation` (peran `user`), `nik` (non-`user`) and `kampus_id` (non-`kampus`) via `removeInputUnless()` so hidden-block values are never validated. It calls `$this->getInputSource()->remove($field)` — DO NOT use `merge(['field' => null])`: `Validator::validatePresent()` is only `Arr::has($this->data, $attribute)`, so a null-valued key still counts as present and non-implicit rules run against it (`min:8` on null measures 0 and fails). Also note `ParameterBag::remove()` takes ONE string, not an array.
