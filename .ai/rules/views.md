---
paths:
  - 'resources/views/**'
---

# Views

## No HTML required attribute in forms
Do not add the HTML `required` attribute to form fields. Validation is enforced server-side via Form Requests (including conditional `required_if` for parent/guardian documents based on status_orang_tua). Keep the red `*` span in labels as a visual hint only.
