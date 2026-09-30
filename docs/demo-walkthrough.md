# Demo walkthrough (reproducible)

Seeded demo logins (password `password`, local development only):

| Role | Email |
|---|---|
| Issuer | issuer@example.test |
| Citizen | citizen@example.test |
| Verifier | verifier@example.test |
| Administrator | admin@example.test |

Seeded demo citizens: Yamikani Phiri (National ID `DEMO0001`, no portal
account) and Tadala Mvula (portal account). Demo credentials cover active,
expired, revoked and replaced states.

## End-to-end scenario

1. Log in as issuer (`issuer@example.test`).
2. Open Search Citizen, search National ID `DEMO0001`.
3. Open the passport and confirm first name + last name.
4. Issue a vaccination credential (e.g. Measles, dose 1).
5. Open Print Certificate and note the credential number and QR code.
6. In a private window, open `/verify`, enter the credential number
   (or scan the QR) and confirm the result is **Valid** with masked identity.
7. Log in as citizen (`citizen@example.test`) and view the same credential
   on My Passport (for a portal citizen such as Tadala Mvula), or note the
   assisted-only path for citizens without accounts (Yamikani Phiri).
8. As issuer, revoke the credential (reason: duplicate, no replacement).
9. Verify the old credential number again and confirm **Revoked** with no
   identity details.
10. As administrator (`admin@example.test`), open Audit Log for the safe
    `credential_issued` / `credential_revoked` rows and open Backups for
    run history.
11. Check the Mailtrap inbox for the issue/revoke emails (only for citizens
    with email) and confirm they contain no National ID, results, or tokens.
12. Run `php artisan backup:run-and-email` and confirm a `backup_completed`
    audit plus a Backups page entry.
13. Describe restore as CLI-only (`backup:restore example-backup.zip
    --force`); execute it only on an isolated disposable test database,
    never during the live demo.

## What this proves

```text
- Smartphone and non-smartphone access support.
- National ID is lookup/identity support, not a password.
- QR contains no medical data.
- Verification uses minimum disclosure.
- Revoked/replaced credentials fail verification.
- Audit logs are privacy-safe.
- Backups are encrypted and not publicly downloadable.
```
