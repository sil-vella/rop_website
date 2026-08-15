<?php
declare(strict_types=1);

$basePath = '..';
$siteName = 'Silvester Vella';
$currentPage = 'about';
$pageTitle = 'About';
$pageDescription = 'About Silvester Vella — personal portfolio.';
$showInNav = true;
$navLabel = 'About';
$headerName = 'Silvester Vella';
$headerTagline = 'self taught, mobile app games and web development';
$contactEmail = '';

ob_start();
?>
  <section id="one">
    <header class="major">
      <h2>About me</h2>
    </header>
    <p>This portfolio is a place to share selected work and make it easy to get in touch.</p>
    <p>I&rsquo;m a self-taught web and app developer with some background in front-end development and WordPress. I enjoy turning ideas into practical digital products, from websites to mobile games published on the App Store and Google Play. I currently work mainly with Flutter and AI-assisted development tools, combining creativity, problem-solving, and a strong understanding of project structure to build and improve real-world applications.</p>
    <p>I&rsquo;m resident in Malta, but I spend months at a time in the Netherlands.</p>
    <h3>Personal projects</h3>
    <p>Under my games brand <strong>Reign of Play</strong> I build and ship my own titles. That includes <strong>Dutch Card Game</strong> — my first mobile app, live on the App Store and Google Play — and <strong>Arcori</strong>, an early-stage collectible multiplayer game. I also created the physical Maltese party card game <strong>Li Ma Tagħmilhiex</strong> (graphics and social content) and the Reign of Play brand website.</p>
    <p>Outside software I keep an art practice: paintings and drawings, plus concrete coffee tables and ornaments.</p>
    <h3>Freelance &amp; client work</h3>
    <p>Alongside personal work I take on freelance web and design projects — often WordPress (including custom child themes and plugins) or custom PHP/HTML sites for organisations and hospitality. Recent examples include restaurant and event sites in the Netherlands, and Maltese clients such as a drama school / production house (including logo design), a medical association, and a social foundation.</p>
    <p>If you&rsquo;d like to collaborate, ask a question, or just say hello, use the contact form on the home page. I read every message and reply as soon as I can.</p>
    <ul class="actions">
      <li><a href="<?= htmlspecialchars($basePath . '/index.php#two', ENT_QUOTES, 'UTF-8') ?>" class="button">View recent work</a></li>
      <li><a href="<?= htmlspecialchars($basePath . '/index.php#three', ENT_QUOTES, 'UTF-8') ?>" class="button">Get in touch</a></li>
    </ul>
  </section>
<?php
$pageContent = ob_get_clean();

require dirname(__DIR__) . '/layout.php';
