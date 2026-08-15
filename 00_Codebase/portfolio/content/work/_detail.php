<?php
declare(strict_types=1);

/**
 * Shared work detail page body. Expects $slug to be set before include.
 */

require dirname(__DIR__, 2) . '/includes/work_items.php';

if (!isset($slug) || !is_string($slug) || $slug === '') {
    http_response_code(500);
    echo 'Work slug not set.';
    exit;
}

$item = portfolio_work_item($slug);
if ($item === null) {
    http_response_code(404);
    echo 'Work item not found.';
    exit;
}

$basePath = '../..';
$siteName = 'Silvester Vella';
$currentPage = 'work-' . $slug;
$pageTitle = $item['title'];
$pageDescription = $item['summary'];
$headerName = 'Silvester Vella';
$headerTagline = 'self taught, mobile app games and web development';
$contactEmail = '';
$heroSrc = trim((string) ($item['full'] ?? ''));

ob_start();
?>
  <section id="one">
    <header class="major">
      <h2><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h2>
    </header>
    <?php if ($heroSrc !== '') : ?>
    <span class="image fit work-detail-image">
      <img
        src="<?= htmlspecialchars($basePath . $heroSrc, ENT_QUOTES, 'UTF-8') ?>"
        alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"
      >
    </span>
    <?php endif; ?>
    <?php if (trim((string) ($item['description'] ?? '')) !== '') : ?>
    <p><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?= $item['body_html'] ?>
    <?php if (!empty($item['gallery']) && is_array($item['gallery'])) : ?>
    <h3>Gallery</h3>
    <div class="row work-gallery">
      <?php foreach ($item['gallery'] as $galleryItem) : ?>
        <article class="col-6 col-12-xsmall work-gallery-item">
          <a href="<?= htmlspecialchars($basePath . $galleryItem['src'], ENT_QUOTES, 'UTF-8') ?>" class="image fit" target="_blank" rel="noopener noreferrer">
            <img
              src="<?= htmlspecialchars($basePath . $galleryItem['src'], ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= htmlspecialchars($galleryItem['alt'] ?? $item['title'], ENT_QUOTES, 'UTF-8') ?>"
            >
          </a>
        </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($item['case_studies']) && is_array($item['case_studies'])) : ?>
    <h3>Case study</h3>
    <?php foreach ($item['case_studies'] as $caseStudy) : ?>
      <?php if (trim((string) ($caseStudy['summary'] ?? '')) !== '') : ?>
      <p><?= htmlspecialchars($caseStudy['summary'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <ul class="actions">
        <li>
          <a
            href="<?= htmlspecialchars($basePath . $caseStudy['url'], ENT_QUOTES, 'UTF-8') ?>"
            class="button primary"
          ><?= htmlspecialchars($caseStudy['label'], ENT_QUOTES, 'UTF-8') ?></a>
        </li>
      </ul>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($item['links']) && is_array($item['links'])) : ?>
    <h3>Links</h3>
    <ul class="actions">
      <?php foreach ($item['links'] as $link) : ?>
        <li>
          <a
            href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"
            class="button<?= !empty($link['primary']) ? ' primary' : '' ?>"
            target="_blank"
            rel="noopener noreferrer"
          ><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <ul class="actions">
      <li><a href="<?= htmlspecialchars($basePath . '/index.php#two', ENT_QUOTES, 'UTF-8') ?>" class="button">Back to work</a></li>
      <li><a href="<?= htmlspecialchars($basePath . '/index.php#three', ENT_QUOTES, 'UTF-8') ?>" class="button">Get in touch</a></li>
    </ul>
  </section>
<?php
$pageContent = ob_get_clean();

require dirname(__DIR__, 2) . '/layout.php';
