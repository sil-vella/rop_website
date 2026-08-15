<?php
declare(strict_types=1);

$basePath = rtrim($basePath ?? '', '/');
$currentPage = $currentPage ?? '';

$contentDir = dirname(__DIR__) . '/content';
$navItems = [];

if (is_dir($contentDir)) {
    foreach (glob($contentDir . '/*.php') ?: [] as $contentFile) {
        $fileBody = file_get_contents($contentFile);
        if ($fileBody === false) {
            continue;
        }

        if (!preg_match('/\$showInNav\s*=\s*(true|false)\s*;/i', $fileBody, $showMatch)) {
            continue;
        }

        if (strtolower($showMatch[1]) !== 'true') {
            continue;
        }

        $slug = pathinfo($contentFile, PATHINFO_FILENAME);
        $label = ucfirst(str_replace(['-', '_'], ' ', $slug));

        if (preg_match('/\$navLabel\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $fileBody, $labelMatch)) {
            $label = trim($labelMatch[1]);
        } elseif (preg_match('/\$pageTitle\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $fileBody, $titleMatch)) {
            $label = trim($titleMatch[1]);
        }

        $navItems[] = [
            'slug' => $slug,
            'label' => $label,
            'url' => $basePath . '/content/' . $slug . '.php',
        ];
    }
}

usort(
    $navItems,
    static fn(array $a, array $b): int => strcasecmp($a['label'], $b['label'])
);
?>
<ul class="site-nav">
  <li>
    <a href="<?= htmlspecialchars($basePath . '/index.php', ENT_QUOTES, 'UTF-8') ?>"<?= $currentPage === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
  </li>
  <?php if ($currentPage === 'home') : ?>
    <li><a href="#two">Work</a></li>
    <li><a href="#three">Contact</a></li>
  <?php endif; ?>
  <?php foreach ($navItems as $item) : ?>
    <li>
      <a href="<?= htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>"<?= $currentPage === $item['slug'] ? ' aria-current="page"' : '' ?>>
        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
