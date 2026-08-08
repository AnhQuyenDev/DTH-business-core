# Admin Marketing / Email navigation fix

This overlay removes Resource-level dependence on legacy department role aliases for navigation.
Admin visibility is explicit via Marketing view gates; operational create/edit actions still use manage gates.

Apply from repository root:

```bash
bash DTH-admin-marketing-nav-fix/apply.sh marketing-email-laravel-v12
cd marketing-email-laravel-v12
php artisan optimize:clear
```

Then sign out/in and refresh `/admin`.
