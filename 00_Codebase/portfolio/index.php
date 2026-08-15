<?php
declare(strict_types=1);

require __DIR__ . '/includes/work_items.php';

$basePath = '';
$siteName = 'Silvester Vella';
$currentPage = 'home';
$pageTitle = 'Home';
$pageDescription = 'Personal portfolio of Silvester Vella — work, projects, and ways to get in touch.';
$headerName = 'Silvester Vella';
$headerTagline = 'self taught, mobile app games and web development';
$contactEmail = '';
$contactAddress = "Malta";
$contactPhone = '';
$contactSource = 'Portfolio';

$workItems = portfolio_work_items();

ob_start();
?>
  <section id="one">
    <header class="major">
      <h2>Hi — I&rsquo;m Silvester.<br>
      Welcome to my portfolio.</h2>
    </header>
    <p>I design and build digital products — from games to web experiences. This site is a personal space for selected work and a simple way to reach me directly.</p>
    <ul class="actions">
      <li><a href="<?= htmlspecialchars($basePath . '/content/about.php', ENT_QUOTES, 'UTF-8') ?>" class="button">About me</a></li>
    </ul>
  </section>

  <section id="two">
    <h2>Recent Work</h2>
    <div class="row">
      <?php foreach ($workItems as $item) : ?>
        <?php
        $detailUrl = $basePath . '/content/work/' . rawurlencode($item['slug']) . '.php';
        ?>
        <article class="col-6 col-12-xsmall work-item">
          <?php if (!empty($item['thumb'])) : ?>
          <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="work-item-media image thumb">
            <img src="<?= htmlspecialchars($basePath . $item['thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>">
          </a>
          <?php endif; ?>
          <h3><a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></a></h3>
          <p><?= htmlspecialchars($item['summary'], ENT_QUOTES, 'UTF-8') ?></p>
          <ul class="actions">
            <li><a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="button small">View project</a></li>
          </ul>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="three">
    <h2>Get in touch</h2>
    <p>Have a project in mind, a question, or just want to say hello? Send a message — I will reply as soon as possible.</p>
    <div class="row">
      <div class="col-8 col-12-small">
        <form id="contact-form" method="post" action="#" data-source="<?= htmlspecialchars($contactSource, ENT_QUOTES, 'UTF-8') ?>" data-recipient="silvester.vella@gmail.com" novalidate>
          <div class="row gtr-uniform gtr-50">
            <div class="col-6 col-12-xsmall">
              <input type="text" name="name" id="name" placeholder="Name" required autocomplete="name">
            </div>
            <div class="col-6 col-12-xsmall">
              <input type="email" name="email" id="email" placeholder="Email" required autocomplete="email">
            </div>
            <div class="col-12">
              <textarea name="message" id="message" placeholder="Message" rows="4" required minlength="10"></textarea>
            </div>
            <div class="col-12">
              <ul class="actions">
                <li><input type="submit" value="Send Message"></li>
              </ul>
            </div>
          </div>
        </form>
        <p id="contact-message" class="contact-message" aria-live="polite"></p>
      </div>
      <div class="col-4 col-12-small">
        <ul class="labeled-icons">
          <li>
            <h3 class="icon solid fa-home"><span class="label">Address</span></h3>
            <?= nl2br(htmlspecialchars($contactAddress, ENT_QUOTES, 'UTF-8')) ?>
          </li>
          <?php if ($contactPhone !== '') : ?>
          <li>
            <h3 class="icon solid fa-mobile-alt"><span class="label">Phone</span></h3>
            <?= htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8') ?>
          </li>
          <?php endif; ?>
          <?php if ($contactEmail !== '') : ?>
          <li>
            <h3 class="icon solid fa-envelope"><span class="label">Email</span></h3>
            <a href="<?= htmlspecialchars('mailto:' . $contactEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></a>
          </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </section>
<?php
$pageContent = ob_get_clean();

require __DIR__ . '/layout.php';
