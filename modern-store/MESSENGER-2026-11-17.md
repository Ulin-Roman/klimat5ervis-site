# Messenger widget — 17 November 2026

Published to the main HostCMS template 3 and ModernCatalog2026 XSL390. Header Telegram/MAX block removed. Fixed blue chat launcher in the bottom-right opens Telegram, MAX and WhatsApp upward on click. Existing business Telegram and MAX URLs preserved; WhatsApp uses the public business phone +79054113948. No messages sent.

Native button, aria-expanded/controls, hidden panel, icon-only links with accessible names, transparent backgrounds, 48px link targets, Escape/focus return, Tab order, outside click, reduced-motion and safe-area support. Cookie notice offset adapts to its visible height; widget stays below header/menu/modal layers. No third-party widget or new network library.

QA: production home, native product7715 and empty native cart at iframe widths320/390/768/1280; no overflow and all panels fit upward. New preview320 tested too. Actual clientWidth is15px narrower due Chromium scrollbar. Desktop cookie notice moved launcher upward108px; keyboard sequence Telegram/MAX/WhatsApp and Escape verified. This does not certify WhatsApp account availability or physical iOS/Android devices.

Files: template3.php is the deployed production parent template; rollback-template3.php is its exact pre-change value. ModernCatalog2026.xsl and structure-source.php are deployed preview sources; rollback counterparts preserve pre-widget state. Messenger-widget.html is the self-contained fragment embedded in both templates. Current screenshots and QA JSON included.

Rollback: restore template3.php from rollback-template3.php in HostCMS template3. Restore XSL390 and structure68 source from rollback counterparts. Do not change CSS/template data, production catalog routes or cart handler.

Cart/checkout modernization remains in progress; no order/payment created. PHP upgrade, full image conversion and server caching remain separate pending stages.
