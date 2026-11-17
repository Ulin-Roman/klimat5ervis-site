# Modern checkout: evidence and implementation boundary

Status: research/design only. No browser, production or existing modern-page changes. Original canonical lib80 and XSL334 snapshots were **not found** in the local shared artifacts during this pass. Do not deploy checkout processing from an assumed generic HostCMS example.

## Verified local entry point

- `/workspace/shared/modern-cart-20261117/ModernCart2026.xsl`: checkout submits the existing basket form by POST to `/category/cart/`, with `step=1`.
- `/workspace/shared/modern-cart-20261117/cart-structure-source.php`: native `Shop_Cart_Controller_Show`, current coupon and native totals; no payment/order handler.
- `/workspace/shared/modern-cart-20261117/cart-structure-config.php`: cart changes, CSRF and POST/redirect/GET; it is not a checkout controller.
- `/workspace/shared/modern-cart-20261117/cart-qa-results.json`: the parent observed the canonical first checkout screen without submitting an order.

The outgoing basket form includes `quantity_{shop_item_id}`, `warehouse_{shop_item_id}`, optional `postpone_{shop_item_id}`, `coupon_text`, and `cart_token`. The clicked checkout button adds `step=1`. The `cart_token` is the modern cart's token; do not assume that the canonical checkout verifies it.

Shop ID is **1**, while Site ID is **2**. These are separate native identifiers.

## Verified controller responsibilities

Official API documents separate address, delivery and payment-selection controllers. Address rendering can expose additional order properties; those must not silently disappear from a new UI. Delivery `setUp()` calculates cart amount/weight. Payment-selection filtering accepts the selected delivery ID. This confirms the presentation components, not this installation's exact numbered step dispatch or required fields.

Payment-handler execution is materially different: the documented handler includes order creation, notification/email operations and payment callbacks. Its `checkBeforeContent` / `checkAfterContent` hooks can process incoming payment state before or after normal page content. They must not be copied into a preview renderer merely to reproduce the existing page shell.

The accessible generated API is labelled 6.x; installed 7.1.7 source and native lib snapshots remain authoritative for exact behavior. A generic step-number sequence is not evidence for this customized installation.

## Recommended implementation

Create a separate `/modern-checkout/` root route and dedicated modern XSL templates for the native presentation stages. Keep the existing `/category/cart/` and its callbacks unchanged. Do not switch the current modern-cart button until the new stages have passed QA.

The preview route should have explicit server-side handling only for the verified presentation actions. It must contain no order/payment-handler execution path, no payment callback dispatch, no one-click checkout branch, no account registration and no mail sending. A disabled final button alone is insufficient: direct POST to a finalization action must also be rejected before any controller with commit side effects runs. Do not invent a native `dryRun()` API.

Keep preview input state in its own session namespace. If a documented presentation controller depends on `hostcmsOrder`, scope that dependency deliberately: preserve the current canonical state, supply preview state only around the rendering call, retain any computed preview delivery quotes, and restore canonical state in `finally` while holding the session lock. This adapter requires verification against the actual controller/handler behavior; it is not implemented here. Never let a preview overwrite an in-progress canonical order.

Use the same native calculation and visibility rules as the canonical flow. Delivery conditions can be handler-generated, so preserve their real identifier shape rather than casting every selection to an integer. Revalidate selections against available methods for the current cart/address. Recalculate and invalidate dependent delivery/payment choices when relevant earlier inputs change. Payment options must respect the actual delivery/payment relation; do not hardcode an assumed cash/card list.

For the UI, use the current storefront header/telephone/cart and a compact progress indicator. The main column contains native labelled fields, grouped by contact/address, delivery and payment; a quieter right column shows the actual cart and totals. On mobile the summary follows the fields. Back/edit actions retain input. Use native required flags and error messages, with errors next to their fields. Do not add compulsory address fields for pickup, omit required native properties, invent delivery promises or replace the existing consent text.

## Evidence still required before PHP/XSL implementation

1. Canonical lib80 source, configuration and parameters: all dispatch branches, callbacks, AJAX actions, quantity/coupon handling and session mutations.
2. XSL334 and every XSL selected by address/delivery/payment/confirmation branches: actual input names, values, required flags, form actions, hidden fields, additional properties, consent and scripts.
3. Exact first operation that commits a `Shop_Order` or calls the selected payment handler; any earlier registration, notification or custom delivery side effects.
4. The active delivery/payment handler code or a verified side-effect boundary for their quote/list operations. Rendering methods can invoke custom events; a name ending in `Show` alone is not a proof of purity.
5. The native data XML contract for each presentation stage, captured without customer data. No production session values or payment secrets should enter a shared QA artifact.

## QA without creating a real order

- First compile and render XSL against synthetic, non-personal fixtures. Check labels, required controls, errors, empty delivery/payment results, summary and narrow widths.
- Verify by source inspection that preview finalization and callback-like requests cannot reach order/payment/mail code. Exercise the rejection path, not the canonical submit endpoint.
- Then use only the verified presentation stages with the existing cart. Check back/forward editing, CSRF, empty cart, changed quantity/coupon/address, unavailable selections and duplicated POSTs.
- Confirm that canonical session/order state is restored after preview rendering and that no registration, notification, order, payment or email was produced. Do not infer this merely from a lack of a confirmation page.
- Keep payment callback URLs and native checkout intact; end the QA run before any real commitment. A future enabled finalization branch needs its own explicit review and validation against the actual native flow.

## Primary references

- [HostCMS payment handlers](https://www.hostcms.ru/documentation/modules/shop/references/payment-systems/handlers/): callback hooks, creation path and email sending.
- [Address controller API](https://hostcms.org/api6/class-Shop_Address_Controller_Show.html): address display and order properties.
- [Delivery controller API](https://hostcms.org/api6/class-Shop_Delivery_Controller_Show.html): native delivery selection and `setUp()` calculations.
- [Payment-selection controller API](https://hostcms.org/api6/class-Shop_Payment_System_Controller_Show.html): filtering by delivery ID.
- [Payment handler API](https://hostcms.org/api6/class-Shop_Payment_System_Handler.html): `execute`, `createOrder`, `_processOrder`, notification and mail methods.
- [Delivery/payment relationships](https://www.hostcms.ru/documentation/modules/shop/references/delivery/delivery-and-payment-systems-relations/): payment availability depends on selected delivery.
