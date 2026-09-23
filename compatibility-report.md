# EB Openverse Block — Compatibility Report

- Plugin: `eb-openverse-block` (main file `eb-openverse-block.php`)
- Version: 1.2.0 → **1.2.1** (patch)
- Branch: `dev` (per user instruction — no new branch created)
- Date: 2026-09-21
- Nothing committed or pushed.

## 1. Detected original baseline

| | Declared (before) | Detected from code |
|---|---|---|
| PHP | `Requires php: 5.6` (readme only; no header) | **5.6-era**. Plugin code uses short arrays (5.4+), closures, no `??`/return types/7.x syntax. `str_contains()` (PHP 8.0) is used but is polyfilled by WP 5.9+. |
| WP | `Requires at least: 5.6` (readme only) | **5.9**. `str_contains()` relies on WP 5.9 polyfill on PHP < 8; `site-editor.php` checks (5.9 FSE); `register_block_type( $path )` path form (5.8+). |
| Tested up to | 6.5 | — |

Header and code disagree: the readme claims WP 5.6, but code needs WP 5.9 on PHP < 8.

## 2. Chosen floor

- Policy minimum: PHP 7.4 / WP 6.0.
- Detected: PHP 5.6 / WP 5.9.
- **Policy won on both** → PHP 7.4 / WP 6.0. User did not override.

## 3. Target range

- Latest stable checked 2026-09-21: **PHP 8.5.10** (php.net releases JSON), **WordPress 7.1.1** (api.wordpress.org version-check).
- Target: **PHP 7.4 → 8.5**, **WP 6.0 → 7.1**.
- Checklist: PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5 · WP 6.0, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 7.0, 7.1.

## 4. Audit — issues found

| # | File:line | Issue | Breaks on | Severity |
|---|---|---|---|---|
| A1 | `lib/style-handler` + `controls` (submodules) | Submodules not initialized; `style-handler.php` `require_once`'d unconditionally → fatal on activation | all | Critical |
| A2 | `includes/class-openverse-ajax.php:91` | `get_object_vars()` on non-object (`remote_post` returns array on WP_Error, `json_decode` may return null) → `TypeError` | PHP 8.0+ | High |
| A3 | `includes/class-openverse-ajax.php:167` | Same `get_object_vars()` TypeError in token generation | PHP 8.0+ | High |
| A4 | `includes/class-openverse-ajax.php:144-145,168-169,200` | Array offset on bool/null + undefined index (`client_id`, `access_token`, `expires_in`) when not registered or API errors | PHP 7.4 warning, 8.0 warning | Medium |
| A5 | `includes/api-class.php:102` | `array_merge( $request['headers'], … )` with undefined `headers` → `TypeError` | PHP 8.0+ | Medium (latent) |
| A6 | `includes/class-openverse-ajax.php:245-249` | `$file` undefined when `image_url` missing → `basename(null)` | PHP 8.1 deprecation | Medium |
| A7 | `includes/class-openverse-ajax.php:266` | `&&` instead of `\|\|` in function-existence guard → possible fatal on undefined function | all | Low |
| A8 | `includes/class-openverse-ajax.php:279` | `mime_content_type()` needs `fileinfo` ext; fatal if missing | all | Low |
| A9 | `includes/helpers.php:50` | `include_once` returns `true` on second include → array offset on bool → `array_merge(null)` TypeError | PHP 8.0+ | Medium (latent) |
| A10 | `eb-openverse-block.php` | No `ABSPATH` guard | all | Low |
| A11 | `eb-openverse-block.php:44-52` | `define()` without `defined()` guard → redefinition warning if init runs twice | all | Low |
| A12 | `includes/helpers.php:60` | `(float) get_bloginfo('version')` passed to JS as `eb_wp_version` — "7.10" becomes 7.1 | WP x.10 releases | Medium — **flagged** |
| A13 | `includes/helpers.php:93` | `(float)` version compare `<= 5.6` — dead below floor | — | see §5 |
| A14 | `includes/class-openverse-ajax.php:55` | All 5 AJAX actions registered as `wp_ajax_nopriv_*` — unauthenticated users can trigger `eb_get_item` (download remote URL → media library) and `eb_get_registration` (overwrites `eb_settings['openverseApi']`) | all | **High (security) — flagged** |
| A15 | `includes/class-openverse-ajax.php:63,111,191,241` | Nonce only verified *if present* (`isset && !verify`) — omitting it bypasses check; no `current_user_can()` anywhere. JS does not send nonces for `eb_get_item`, `eb_get_registration`, `eb_openverse_token` | all | **High (security) — flagged** |
| A16 | `includes/class-openverse-ajax.php:209` | `self::isset_check( $limit )` reads `$_POST[12]` → `page_size` always empty (intended 12) | — | Low — **flagged** (behavior) |
| A17 | `includes/api-class.php:89-103` / ajax callers | Callers pass the header array as `$apiKey` and the `timeout` array as `$headers` → `X-API-KEY` header is an array ("Array to string conversion" warning), `timeout` sent as a header, not applied | PHP 8 warning | Medium — **flagged** (behavior) |
| A18 | `includes/class-openverse-ajax.php` (4×) | Text domain `essential-blocks` instead of `eb-openverse-block` | — | Low — **flagged** |
| A19 | `includes/font-loader.php` | Google Fonts enqueued on `admin_enqueue_scripts` only → not injected into the editor canvas iframe (always iframed in WP 7.1) | WP 7.1 (editor visual) | Medium — **flagged** |
| A20 | `includes/font-loader.php:75` | `wp_register_style` without version arg | — | Low — flagged (changes URL `?ver=`) |
| A21 | `lib/style-handler/includes/class-parse-css.php:33` | `array_key_exists()` on `eb_settings` if option is a non-array → TypeError | PHP 8.0+ | Medium — flagged (shared submodule) |
| A22 | `lib/style-handler/style-handler.php:204` | `array_merge()` on non-array meta/option → TypeError | PHP 8.0+ | Low — flagged (shared submodule) |
| A23 | `lib/style-handler/style-handler.php:438-441,483` | `AssetGeneration` / `ESSENTIAL_BLOCKS_DIR_PATH` only exist with Essential Blocks active; hooks fired only by EB, so unreachable standalone | PHP 8.0 (undefined const = Error) | Low — flagged (shared submodule) |

No hits for: `create_function`, `each()`, `mysql_*`, `ereg*`, `${var}` interpolation, `FILTER_SANITIZE_STRING`, `strftime`, `utf8_encode`, implicit nullable params, dynamic properties, curly offsets, non-canonical casts, jQuery Migrate patterns, REST routes, `$wpdb` without `prepare()`, textdomain loading before `init`.

## 5. Dead version-check branches (floor raised to WP 6.0)

| File | Line | Condition | What the branch does | Single remaining path if removed | Decision |
|---|---|---|---|---|---|
| `includes/helpers.php` | 93 | `(float) get_bloginfo('version') <= 5.6` | Returns block name string instead of directory path for `register_block_type()` on WP ≤ 5.6 | `return $blockPath;` (register from `block.json` directory) | **awaiting decision** |

## 6. Fixes applied

| Issue | Fix |
|---|---|
| A1 | `git submodule update --init` for both; `controls` checked out at `50804e5` (user request). `require_once` of style-handler now wrapped in `file_exists()` (`eb-openverse-block.php:33`). |
| A2, A3 | `is_object( $response ) ? get_object_vars( $response ) : (array) $response` |
| A4 | `?? ''` / `?? null` on offsets; `expires_in` cast `(int) ( … ?? 0 )` — same resulting transient TTL as before |
| A5 | `$request['headers'] ?? array()` |
| A6 | `$file = '';` initialized before `isset()` check |
| A7 | Guard changed to `\|\|` |
| A8 | `function_exists( 'mime_content_type' )` guard, falls into existing "unknown mime → return false" path |
| A9 | `include_once` → `include` (asset file returns an array every time) |
| A10 | `ABSPATH` guard added |
| A11 | `defined()` guards on the three constants |

## 7. Flagged, not auto-fixed (need decision)

1. **A14/A15 — AJAX security (recommend fix).** Drop `wp_ajax_nopriv_*` registration (block is editor-only; frontend JS makes no AJAX calls), make nonce mandatory, send nonces from JS for `eb_get_item` / `eb_get_registration` / `eb_openverse_token`, add `current_user_can( 'upload_files' )` for `eb_get_item` and `current_user_can( 'edit_posts' )` for the rest. Requires JS change + rebuild.
2. **A12 — `eb_wp_version`.** Controls JS consumes a float. Options: keep, or pass `get_bloginfo('version')` string and update JS consumers.
3. **A16 — `page_size`.** Fix to `12` would change result count (currently API default).
4. **A17 — API arg order.** Fixing puts headers/timeout where intended; changes outbound request shape.
5. **A18 — text domain.** Switch to `eb-openverse-block`; existing translations under `essential-blocks` would stop matching.
6. **A19 — fonts in iframed editor.** Also hook the loader on `enqueue_block_assets` so fonts render in the WP 7.1 canvas.
7. **A20 — font style version.** Adding version changes asset URL.
8. **A21–A23 — style-handler.** Shared submodule used by sibling plugins; fix upstream in `EssentialBlocks/style-handler`, not here.

## 8. Old-vs-new conflicts

None. All fixes use syntax valid on PHP 7.4 (`??`) and APIs available in WP 6.0.

## 9. Declared compatibility now

- Header: `Requires at least: 6.0`, `Tested up to: 7.1`, `Requires PHP: 7.4`, `Version: 1.2.1`
- readme.txt: `Requires at least: 6.0`, `Requires PHP: 7.4` (casing fixed), `Tested up to: 7.1`, `Stable tag: 1.2.1`, changelog entry added
- `EB_OPENVERSE_BLOCK_VERSION` and `package.json`: 1.2.1

## 10. Verification

- `php -l` on every plugin PHP file (incl. submodule `lib/style-handler` and `dist/*.asset.php`) under **PHP 8.5.8**: no syntax errors.
- PHP 7.4 binary not available locally — 7.4 compatibility checked by review only (no 8.x-only syntax introduced).
- `phpcs`: not installed — skipped.
- Not runtime-tested in WordPress.
