<?php
Core_Page::instance()->staticCache(FALSE);
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: private, no-store');
?>