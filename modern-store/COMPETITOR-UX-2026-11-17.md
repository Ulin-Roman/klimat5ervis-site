# Competitor UX review — 17 November 2026

Primary storefronts reviewed: [MirCli](https://mircli.ru/nastennye-kondicionery/), [Русклимат](https://www.rusklimat.ru/catalog/konditsionery/split-sistemy), [Splitu](https://splitu.ru/), [Ставим Холод](https://stavimholod.ru/catalog). No claims about ranking, advertising longevity or conversion uplift.

MirCli: area shortcuts and filters for price, brand, area, noise, inverter and Wi-Fi; consistent card facts. Русклимат: category/area selection, sorting, delivery/pickup information and equipment/installation separation. Splitu: room-area selection on the first screen, visible search and service links, cart/catalog entry. Ставим Холод: explicit equipment versus installed pricing and readable filter controls. Competitor tariffs, availability, reviews and promotional claims are not imported into our store.

Our implementation priorities: visible selection and search; actual native filters and concise card facts; compact mobile controls; transparent server-priced cart; delivery and installation linked to our own conditions. Room area must not be inferred from BTU: many imported products have capacity text but no normalised area. A real area selector needs a separate data-quality pass.

Completed in this stage: messenger launcher on production plus preview; modern homepage, native cart, information pages, brand/technical filters and product cards; responsive WebP variants for all 11,849 groups in the current image queue, with zero skipped. Checkout still uses the existing canonical HostCMS flow. Its redesign and complete order testing, PHP compatibility work, shared public-data caching, and automatic processing of future image uploads remain pending. The main routes have not been switched to the preview.
