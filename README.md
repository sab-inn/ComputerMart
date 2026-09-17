# computemart (PHP version)

The same marketplace as the .NET/Razor Pages project, reimplemented in
plain PHP + PDO against its own MariaDB database. Same schema design
(same table names conceptually, same relationships), same htmx-driven
interactivity, same eSewa integration — different language, so you can
compare the two side by side.

## Stack

- **Plain PHP 8** (no framework) — one PHP file per URL, the classic PHP
  pattern. `includes/` holds shared logic (DB connection, auth, eSewa),
  `partials/` holds reusable HTML fragments included by multiple pages.
- **PDO with prepared statements** everywhere — no string-concatenated
  SQL, so there's no SQL-injection surface.
- **Sessions** (`$_SESSION`) for login state, `password_hash()` /
  `password_verify()` (bcrypt) for passwords — PHP's built-in equivalent
  of what ASP.NET Core Identity does for you.
- **htmx** — the exact same library and the same attributes as the .NET
  version, since it's backend-agnostic. Live search, cart updates, and
  admin actions work identically.
- **Google OAuth** — implemented by hand with cURL (no Composer
  dependency): redirect to Google, exchange the code for a token, ask
  Google's userinfo endpoint who it belongs to. ASP.NET Core does this
  for you via middleware; here you can see every step.
- **eSewa ePay v2** — a direct PHP port of the same signing/verification
  logic as the .NET version (`includes/esewa.php`).

## One-time setup

1. **Start the database** (separate from the .NET project's, on port 3307
   so both can run at once):
   ```bash
   docker compose up -d
   ```

2. **Load the schema**
   ```bash
   docker exec -i computemart-php-db mysql -ucomputemart -pcomputemart_php_pw computemart_php < database/schema.sql
   ```
   This creates every table and seeds categories, the demo admin/seller
   accounts, and three sample products — no separate migration step
   needed, since there's no ORM generating migrations here.

3. **Run it** with PHP's built-in dev server (fine for local testing;
   use real Apache/nginx + php-fpm for anything beyond that):
   ```bash
   php -S localhost:8000
   ```
   Open `http://localhost:8000`.

Demo logins (same as the .NET version, shown on the login page too):
- **Admin:** admin@computemart.local / Admin@12345
- **Seller:** seller@computemart.local / Seller@12345

### Google sign-in (optional)

1. Reuse the same OAuth Client ID from the .NET setup, or create a new
   one in the [Google Cloud Console](https://console.cloud.google.com/apis/credentials).
2. Add `http://localhost:8000/google_callback.php` as an authorized
   redirect URI (this one **is** something you configure — set by
   `GOOGLE_REDIRECT_URI` in `config.php`, unlike the .NET version's fixed
   `/signin-google` path).
3. Fill in `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `config.php`.
   Leave them blank and the Google button just won't appear.

### eSewa payments

Same UAT test credentials as the .NET version, already in `config.php`.
Test eSewa login: ID `9806800001` (through `...005`), password
`Nepal@123`, MPIN `1122`.

## How it maps to the .NET version

| .NET / Razor Pages | PHP |
|---|---|
| `ApplicationDbContext` (EF Core) | `includes/db.php` (`PDO`) |
| ASP.NET Core Identity | `includes/auth.php` (`password_hash`, `$_SESSION`) |
| `[Authorize(Roles = "...")]` | `require_role('seller')` etc. |
| Antiforgery token | `csrf_token()` / `verify_csrf()` in `includes/auth.php` |
| Razor Page named handlers (`?handler=X`) | Separate `.php` files per action (`cart_add.php`, `seller/toggle_product.php`, ...) |
| `<partial name="..." />` | `require __DIR__ . '/partials/....php'` (sharing PHP's variable scope) |
| `Services/EsewaService.cs` | `includes/esewa.php` |

## What's simplified

Same list as the .NET README: no email confirmation, no rate limiting on
login/register, no protection against two cart-quantity clicks racing
each other. Additionally, PHP's built-in dev server (`php -S`) is
single-threaded and only for local testing — don't use it for anything
beyond that.
