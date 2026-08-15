<?php
declare(strict_types=1);

/**
 * Full page shell. Set these before including this file:
 * - $pageTitle (string)
 * - $pageContent (string, HTML)
 * Optional: $pageDescription, $currentPage ('home'|'about'|...), $siteName, $basePath,
 *           $headerName, $headerTagline, $avatarSrc, $contactEmail, $socialLinks
 */
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/content.php';
require __DIR__ . '/includes/footer.php';
