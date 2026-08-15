<?php
declare(strict_types=1);

$siteName = $siteName ?? 'Silvester Vella';
$basePath = rtrim($basePath ?? '', '/');
$headerName = $headerName ?? $siteName;
$headerTagline = $headerTagline ?? 'self taught, mobile app games and web development';
$avatarSrc = $avatarSrc ?? ($basePath . '/assets/images/profile.png');
$avatarAlt = $avatarAlt ?? ($siteName . ' profile');
?>
<header id="header">
  <div class="inner">
    <a href="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="image avatar">
      <img
        src="<?= htmlspecialchars($avatarSrc, ENT_QUOTES, 'UTF-8') ?>"
        alt="<?= htmlspecialchars($avatarAlt, ENT_QUOTES, 'UTF-8') ?>"
      >
    </a>
    <h1>
      <strong><?= htmlspecialchars($headerName, ENT_QUOTES, 'UTF-8') ?></strong>,
      <?= htmlspecialchars($headerTagline, ENT_QUOTES, 'UTF-8') ?>
    </h1>
    <?php require __DIR__ . '/nav.php'; ?>
  </div>
</header>
