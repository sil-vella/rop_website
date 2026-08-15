<?php
declare(strict_types=1);

$year = (string) (int) date('Y');
$siteName = $siteName ?? 'Silvester Vella';
$basePath = rtrim($basePath ?? '', '/');
$contactEmail = $contactEmail ?? '';
$socialLinks = $socialLinks ?? [
    ['url' => 'https://www.instagram.com/sil.vella', 'icon' => 'fa-instagram', 'label' => 'Instagram', 'brand' => true],
    ['url' => 'https://github.com/sil-vella', 'icon' => 'fa-github', 'label' => 'GitHub', 'brand' => true],
    ['url' => 'https://www.linkedin.com/in/silvester-vella/', 'icon' => 'fa-linkedin-in', 'label' => 'LinkedIn', 'brand' => true],
];
if ($contactEmail !== '') {
    $socialLinks[] = [
        'url' => 'mailto:' . $contactEmail,
        'icon' => 'fa-envelope',
        'label' => 'Email',
        'brand' => false,
    ];
}
?>
<footer id="footer">
  <div class="inner">
    <ul class="icons">
      <?php foreach ($socialLinks as $link) : ?>
        <?php
        $iconClass = !empty($link['brand'])
            ? 'icon brands ' . $link['icon']
            : 'icon solid ' . $link['icon'];
        $isExternal = str_starts_with($link['url'], 'http');
        ?>
        <li>
          <a
            href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"
            class="<?= htmlspecialchars($iconClass, ENT_QUOTES, 'UTF-8') ?>"
            <?php if ($isExternal) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
          >
            <span class="label"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <ul class="copyright">
      <li>&copy; <?= $year ?> <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></li>
    </ul>
  </div>
</footer>

<script src="<?= htmlspecialchars($basePath . '/assets/js/jquery.min.js', ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($basePath . '/assets/js/browser.min.js', ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($basePath . '/assets/js/breakpoints.min.js', ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($basePath . '/assets/js/util.js', ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($basePath . '/assets/js/main.js', ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($basePath . '/assets/js/contact.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
