<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Untitled';
$pageDescription = $pageDescription ?? '';
$basePath = rtrim($basePath ?? '', '/');

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow', true);
}
?>
<!DOCTYPE HTML>
<!--
	Strata by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <meta name="robots" content="noindex, nofollow">
  <?php if ($pageDescription !== '') : ?>
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/main.css', ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars($basePath . '/assets/css/site.css', ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="is-preload">
