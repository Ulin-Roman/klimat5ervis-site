# Checkout preview — deployed test branch

Status recorded on 2026-11-17T11:56:52.632384+00:00. The isolated preview is deployed at `https://klimat5ervis.ru/modern-cart/?view=checkout`. This is a **test checkout branch**, not completed production checkout/order integration. The source in this commit matches the deployed test branch.

## What is included

Only new preview helpers/assets, two branch snippets, the full updated modern-cart source/config candidates and this documentation. No original native HostCMS libraries, module trees, bootstrap/index copies, before-snapshots, database credentials, customer records or order records are included.

| Repository file | Existing hosting destination |
| --- | --- |
| `modern-store/cart-structure-source.php` | `public_html/hostcmsfiles/structure/Structure69.php` |
| `modern-store/cart-structure-config.php` | `public_html/hostcmsfiles/structure/StructureConfig69.php` |
| Four `checkout-*.php` helper files in this directory | `public_html/hostcmsfiles/.klimat-checkout-preview-20261117/` |
| `private-htaccess.txt` | `.htaccess` in that private helper directory |
| `checkout-native.css`, `checkout-native.js` | `public_html/assets/checkout-preview-20261117/` |

The full cart files are the deployed source files. Before patching, both live source files were read and compared their normalized contents with the saved baseline. Ordinary `/modern-cart/` retains its prior code after the new early guard; `/category/cart/` remains the canonical order flow.

The outer release `checkout-live-source.zip` is a **repository source archive** rooted at `modern-store/`; it is not a hosting-root/public_html deployment ZIP. `tree-elements.json` contains matching UTF-8 Git tree entries. Deployment and source publication are recorded separately.

## Implementation boundaries

Both config and source branch on an explicit `view` query before any existing modern-cart session/controller/mutation logic. Exactly `view=checkout` is accepted; extra/malformed query keys are rejected. Helpers never include native lib80/config or any payment/delivery handler.

The preview reads only the current browser's guest cart and public Shop1/Site2 product, delivery and payment data. The installed default driver stores `shop_warehouse_id` inside each numeric item row; zero/nonzero values are accepted without stock lookup/mutation. Composite/custom cart keys fail closed. Product/group/modification public and active checks are bounded.

Pricing is explicitly preliminary: native base/quantity-price methods, native currency conversion and public tax inclusion, without promotional item discounts, coupons, cart-wide discounts, gifts, bonuses or personal conditions. `getPrices()` is intentionally excluded because its active-discount check can read the current customer even when the price controller has `siteuser(NULL)`.

Only active public group0 type0 deliveries are shown, selected by native condition rules with zero geography/no requested time. Each delivery gets a fresh payment model constrained to Shop1, active/public group0 and its permitted payment relation. No payment processing occurs. Address/comment are optional; name, phone and email are required.

POST only validates CSRF, scalar/length limits, current allowed delivery/payment choices and contacts, then renders an escaped read-only review. Only a separate preview CSRF token/expiry are stored. Contacts are not written to native checkout state or stored by this implementation. No browser AJAX/storage, cart writes, customer/order/transaction reads or order/payment handler calls occur in the new branch. Ordinary native quantity-price and condition-price hooks remain; absolute hook freedom is not claimed.

All statuses/headers are synchronized on `Core_Page::instance()->response` and PHP. Both branches independently apply no-store/noindex/CSP and can report 400/503 before helper loading. Native frontend license/domain initialization remains intact. Optional photographs pass through the existing vetted WebP helper, with original-image fallback and 112px summary sizing.

## Runtime observations

- Guest empty-cart screen rendered before adding one test item.
- Ballu item7715 rendered at 39990, quantity 1.
- Native courier 300 and pickup 0 were offered with cash payment options.
- 112px responsive WebP product image loaded.
- Empty POST displayed three accessible required-contact errors.
- A valid synthetic POST displayed the read-only review; script-like comment content appeared as text.
- Quantity 2 regression: ordinary cart 79980; preview 80280 including courier 300. Extra GET `view=checkout&add=...` was rejected and the cart remained at quantity 2.
- Private helper direct access displayed Forbidden. One H1, unique labels and no overflow were observed at viewport 1348px; the Telegram/MAX/WhatsApp launcher opened upward.
- The exercised preview paths displayed no order/payment action and reached no native order/payment handler in the isolated implementation. No new customer creation is dispatched by this code. No customer/order database audit was performed or claimed.

**Cleanup verified through the ordinary UI:** the owned test item was removed with the native Delete action. Both the ordinary modern cart and the new preview are empty. Contact fields were blank on GET before the supplied desktop screenshot; the screenshot contains no personal data.

## Verification limits and next checks

Local Node syntax, static forbidden-dispatch checks, frozen-file hashes and archive/source byte parity passed. Source and config guards precede the existing cart controller/session paths. PHP CLI is unavailable locally; no PHP CLI lint is claimed. Server execution confirms only the exercised rendering/validation paths, not all branches or the whole store.

Still pending at this snapshot:

- Independent HTTP response-header/status verification, including no-store/noindex/CSP and expected 4xx responses.
- Negative-CSRF, unsupported method, unknown POST parameter and malformed-payload runtime cases. Extra GET rejection has been observed, but its HTTP status has not been independently verified.
- Mobile viewport widths/overflow and full keyboard regression checks.
- Complete native checkout/order/payment/email integration and end-to-end production order tests; these are outside this preview's implementation.

Do not extend the success report beyond these observed paths. Do not submit a real order or payment as part of preview QA.
