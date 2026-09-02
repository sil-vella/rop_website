<?php
declare(strict_types=1);

/**
 * Portfolio work items (SSOT for home grid + detail pages).
 *
 * @return array<string, array{
 *   slug: string,
 *   title: string,
 *   summary: string,
 *   description: string,
 *   body_html: string,
 *   full: string,
 *   thumb: string,
 *   gallery: list<array{src: string, alt: string}>,
 *   case_studies?: list<array{label: string, url: string, summary?: string}>,
 *   links: list<array{label: string, url: string, primary?: bool}>
 * }>
 */
function portfolio_work_items(): array
{
    return [
        'dutch' => [
            'slug' => 'dutch',
            'title' => 'Dutch Card Game',
            'summary' => 'First app under my games brand Reign of Play — Flutter client; Python, Dart, MongoDB, and Redis in Docker; Firebase Analytics and AdMob.',
            'description' => 'My first shipped app, with no prior app experience: Flutter on iOS and Android, Python for user logic, Dart for gameplay, MongoDB and Redis in Docker, plus Firebase Analytics and Google AdMob on the client.',
            'body_html' => <<<'HTML'
<p>Dutch is the <strong>first app I ever built</strong> — I had no prior mobile or multiplayer app experience when I started. It sits under my games brand <strong>Reign of Play</strong>. I shipped it end to end: client, backends, data stores, and both store releases. I also designed the <strong>app logo</strong>.</p>
<h3>Client</h3>
<ul>
  <li><strong>Flutter</strong> for the full iOS and Android experience — UI, session handling, and talking to both backends.</li>
  <li><strong>Firebase Analytics</strong> for product and usage insight on the client.</li>
  <li><strong>Google AdMob</strong> for in-app ads.</li>
</ul>
<h3>Backends</h3>
<ul>
  <li><strong>Python</strong> owns user-facing logic: accounts, sessions, and the product/API layer around the game.</li>
  <li><strong>Dart</strong> owns gameplay: live match state, turns, and the real-time path players feel when cards are played.</li>
</ul>
<h3>Data &amp; infrastructure</h3>
<ul>
  <li><strong>MongoDB</strong> for persistent application data (users, game-related records, and the broader product store).</li>
  <li><strong>Redis</strong> for fast caching, sessions, and real-time messaging between services.</li>
  <li><strong>Docker</strong> runs the stack: Python, Dart, MongoDB, and Redis each live in their own containers.</li>
</ul>
<h3>What I cared about</h3>
<ul>
  <li>Learning by shipping: picking a stack and carrying it through to production stores.</li>
  <li>Owning the product end to end — including branding (logo) as well as engineering.</li>
  <li>Clear boundaries so user logic and game logic can evolve independently.</li>
  <li>A client that stays thin where it should — presentation and orchestration, not authoritative rules.</li>
</ul>
HTML,
            'full' => '/assets/images/work/dutch_app_mockup001.png',
            'thumb' => '/assets/images/work/dutch_app_mockup001.png',
            'gallery' => [
                [
                    'src' => '/assets/images/work/dutch_app_mockup001.png',
                    'alt' => 'Dutch Card Game app mockup',
                ],
                [
                    'src' => '/assets/images/work/dutch.jpg',
                    'alt' => 'Dutch Card Game logo',
                ],
            ],
            'case_studies' => [
                [
                    'label' => 'Read the case study',
                    'url' => '/content/work/case_studies/dutch/case-study-dutch-card-game.html',
                    'summary' => 'Solo build: multiplayer stack, ops desk, and how the product shipped end to end.',
                ],
            ],
            'links' => [
                ['label' => 'Website', 'url' => 'https://dutch.reignofplay.com', 'primary' => true],
                ['label' => 'App Store', 'url' => 'https://apps.apple.com/us/app/dutch-card-game/id6772967073'],
                ['label' => 'Google Play', 'url' => 'https://play.google.com/store/apps/details?id=com.reignofplay.dutch'],
            ],
        ],
        'dash-ops' => [
            'slug' => 'dash-ops',
            'title' => 'Ops Dashboard · Marketing Desk',
            'summary' => 'Local browser twin of my CLI runner — scripts, planning, social publish, revenue, and downloads in one window on the same env.',
            'description' => 'Solo-built local ops dashboard: run automation scripts, plan work, publish social, and track store revenue and downloads next to the same environment the CLI uses.',
            'body_html' => <<<'HTML'
<p>A CLI alone is not an ops surface. Launches and builds fit a terminal menu; drafting social posts, checking store revenue, logging expenses, and reading product docs do not — and they should not invent a second credential store in the browser.</p>
<p>This dashboard is the <strong>opt-in browser twin of <code>wfrun</code></strong>: same script discovery and env files, plus workflows that never belonged in a numbered menu.</p>
<h3>What it covers</h3>
<ul>
  <li><strong>Ops dash</strong> — scripts with an embedded terminal, Task Manager, in-app docs, and case study HTML.</li>
  <li><strong>Marketing desk</strong> — compose once, attach media, publish to Meta / YouTube / TikTok; saved drafts, live post metrics, and a campaign queue.</li>
  <li><strong>Revenue &amp; downloads</strong> — AdMob, Play, and App Store money (estimated vs settled), install units, and a local expense log — separate subtabs, shared store credentials.</li>
</ul>
<h3>Stack</h3>
<ul>
  <li><strong>aiohttp</strong> local dash server with <strong>xterm.js</strong> for live script PTYs.</li>
  <li>Social: Meta Graph, YouTube Data API, TikTok Posting API.</li>
  <li>Stores: Play GCS, App Store Connect, AdMob.</li>
</ul>
<h3>What I cared about</h3>
<ul>
  <li>One local window for day-to-day ops, docs, money, and social — same env as the CLI.</li>
  <li>Manual posts and scheduled campaign drips sharing one contract.</li>
  <li>Portable into product repos (Dutch, Arcori) without rebuilding the desk.</li>
</ul>
HTML,
            'full' => '/content/work/case_studies/dash-ops/images/01.png',
            'thumb' => '/content/work/case_studies/dash-ops/images/01.png',
            'gallery' => [
                [
                    'src' => '/content/work/case_studies/dash-ops/images/01.png',
                    'alt' => 'Ops dashboard — main tabs',
                ],
                [
                    'src' => '/content/work/case_studies/dash-ops/images/02.jpg',
                    'alt' => 'Scripts — accordion and terminal',
                ],
                [
                    'src' => '/content/work/case_studies/dash-ops/images/04.png',
                    'alt' => 'Revenue — KPIs and series table',
                ],
                [
                    'src' => '/content/work/case_studies/dash-ops/images/06.png',
                    'alt' => 'Marketing — compose and saved posts',
                ],
            ],
            'case_studies' => [
                [
                    'label' => 'Read the case study',
                    'url' => '/content/work/case_studies/dash-ops/case-study-ops-dashboard.html',
                    'summary' => 'Solo build: local ops plane, marketing desk, and store revenue next to the CLI.',
                ],
            ],
            'links' => [],
        ],
        'arcori' => [
            'slug' => 'arcori',
            'title' => 'Arcori',
            'summary' => 'Collectible multiplayer game in Velora under Reign of Play — early stages; stack caps, slam to flip, score, and collect.',
            'description' => '',
            'body_html' => <<<'HTML'
<p>Arcori is a collectible multiplayer game set in the mythic world of <strong>Velora</strong>, under my games brand <strong>Reign of Play</strong>. Players stack themed Arcori caps and slam a Slammer into the pile to flip them — each flip scores, and consecutive flips build Mastery toward new generations of a design. Collection, economy, and an evolving world chronicle sit alongside the core slamming loop.</p>
<p>The project is in its <strong>early stages</strong>. It runs on the same structured stack I built after Dutch for future apps — Flutter on the client, FastAPI for product and user logic, and Dart for realtime play.</p>
<h3>Stack</h3>
<ul>
  <li><strong>Frontend — Flutter</strong>: Riverpod for state, go_router for navigation, HTTP + WebSockets, secure storage, Firebase Analytics.</li>
  <li><strong>Backend — Python FastAPI</strong> on Uvicorn/Gunicorn: SQLAlchemy + Alembic, PostgreSQL (psycopg), Redis, JWT/bcrypt auth.</li>
  <li><strong>Realtime — Dart Shelf</strong>: WebSocket / realtime backend alongside the Python API.</li>
</ul>
<h3>Why this project</h3>
<ul>
  <li>A deeper game loop than Dutch: slamming, Mastery, collection, and an evolving world.</li>
  <li>Clear separation of concerns: Flutter client, FastAPI for product/user logic, Dart for realtime play.</li>
  <li>PostgreSQL + Redis for persistence and speed alongside live multiplayer.</li>
</ul>
HTML,
            'full' => '/assets/images/work/arcori_logo.jpg',
            'thumb' => '/assets/images/work/arcori_logo.jpg',
            'gallery' => [
                [
                    'src' => '/assets/images/work/arcori_logo.jpg',
                    'alt' => 'Arcori logo',
                ],
            ],
            'links' => [],
        ],
        'kaatje' => [
            'slug' => 'kaatje',
            'title' => 'Kaatje bij de Sluis',
            'summary' => 'Custom PHP website for a restaurant & résidence in Blokzijl — plus graphic work used on the site and staff clothing.',
            'description' => 'Website for Restaurant & Résidence Kaatje bij de Sluis: a long-running culinary destination on both sides of the lock in historic Blokzijl.',
            'body_html' => <<<'HTML'
<p>I built the public website for <strong>Kaatje bij de Sluis</strong> — Restaurant &amp; Résidence in Blokzijl. It covers the restaurant and résidence story, menus, events, gift vouchers, reservations, gallery, and contact.</p>
<p>The gallery images are my <strong>graphic work</strong> — used on the website and on employee clothing.</p>
<h3>Scope</h3>
<ul>
  <li>Restaurant &amp; résidence presentation, dynamic menu, agenda, and impressie gallery.</li>
  <li>Table reservations, digital gift vouchers, vacancies, and contact.</li>
  <li>Brand graphics for the site and staff clothing.</li>
</ul>
<h3>Stack</h3>
<ul>
  <li><strong>PHP</strong> on nginx, with custom <strong>HTML / CSS / JavaScript</strong>.</li>
  <li><strong>GoTable</strong> for reservations; WhatsApp for chat.</li>
</ul>
HTML,
            'full' => '/assets/images/work/Kaatje001.png',
            'thumb' => '/assets/images/work/Kaatje001.png',
            'gallery' => [
                [
                    'src' => '/assets/images/work/Kaatje001.png',
                    'alt' => 'Kaatje bij de Sluis conceptual collage',
                ],
                [
                    'src' => '/assets/images/work/kaatje004.jpg',
                    'alt' => 'Kaatje wine and food line-art mark',
                ],
                [
                    'src' => '/assets/images/work/kaatje003.png',
                    'alt' => 'Kaatje wine bottle and glass graphic',
                ],
                [
                    'src' => '/assets/images/work/kaatje002.jpg',
                    'alt' => 'Kaatje work graphic',
                ],
            ],
            'links' => [
                ['label' => 'Website', 'url' => 'https://kaatje.nl', 'primary' => true],
            ],
        ],
        'li-ma-taghmilhiex' => [
            'slug' => 'li-ma-taghmilhiex',
            'title' => 'Li Ma Tagħmilhiex',
            'summary' => 'Physical Maltese party card game — all graphic work and social media content for Reign of Play.',
            'description' => '',
            'body_html' => <<<'HTML'
<p><strong>Li Ma Tagħmilhiex</strong> is a physical Maltese party card game under Reign of Play. Players draw challenge cards in Maltese — act, score points by level, and keep the table moving.</p>
<p>I did <strong>all the graphic work</strong> (box, cards, and visuals) and the <strong>social media content</strong> that promoted the game.</p>
<div class="work-video-embed">
  <iframe width="560" height="315" src="https://www.youtube.com/embed/KneCNVXH1vs?si=mK2NWcPjYUjilA1L" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
</div>
HTML,
            'full' => '/assets/images/work/lmt/li-ma-taghmilhiex-001.png',
            'thumb' => '/assets/images/work/lmt/li-ma-taghmilhiex-001.png',
            'gallery' => [
                [
                    'src' => '/assets/images/work/lmt/li-ma-taghmilhiex-001.png',
                    'alt' => 'Li Ma Tagħmilhiex box packaging',
                ],
                [
                    'src' => '/assets/images/work/lmt/li-ma-taghmilhiex-002.jpg',
                    'alt' => 'Li Ma Tagħmilhiex challenge card mockup',
                ],
                [
                    'src' => '/assets/images/work/lmt/li-ma-taghmilhiex-003.jpg',
                    'alt' => 'Li Ma Tagħmilhiex cards',
                ],
                [
                    'src' => '/assets/images/work/lmt/li-ma-taghmilhiex-004.jpg',
                    'alt' => 'Li Ma Tagħmilhiex cards',
                ],
                [
                    'src' => '/assets/images/work/lmt/follow-us-on-001.png',
                    'alt' => 'Li Ma Tagħmilhiex social media follow graphic',
                ],
                [
                    'src' => '/assets/images/work/lmt/fathers-day-001.png',
                    'alt' => 'Li Ma Tagħmilhiex Father\'s Day social graphic',
                ],
                [
                    'src' => '/assets/images/work/lmt/SEP2023-offer-001.png',
                    'alt' => 'Li Ma Tagħmilhiex September offer social graphic',
                ],
                [
                    'src' => '/assets/images/work/lmt/biting-lemon-001.jpg',
                    'alt' => 'Li Ma Tagħmilhiex social graphic',
                ],
                [
                    'src' => '/assets/images/work/lmt/ihossa-filgroup-lee-g-holding-card-001.png',
                    'alt' => 'Li Ma Tagħmilhiex social graphic with cards',
                ],
                [
                    'src' => '/assets/images/work/lmt/qabzet-001.png',
                    'alt' => 'Li Ma Tagħmilhiex social graphic',
                ],
                [
                    'src' => '/assets/images/work/lmt/smell-feet-002.jpg',
                    'alt' => 'Li Ma Tagħmilhiex social graphic',
                ],
                [
                    'src' => '/assets/images/work/lmt/xeba-misthijja-001.png',
                    'alt' => 'Li Ma Tagħmilhiex social graphic',
                ],
            ],
            'links' => [
                ['label' => 'Reign of Play page', 'url' => 'https://reignofplay.com/li-ma-taghmilhiex.html', 'primary' => true],
            ],
        ],
        'reignofplay' => [
            'slug' => 'reignofplay',
            'title' => 'Reign of Play',
            'summary' => 'Games brand website — titles, game pages, and contact. Built with PHP and HTML.',
            'description' => '',
            'body_html' => <<<'HTML'
<p>The public website for my games brand <strong>Reign of Play</strong> — home for Dutch, Li Ma Tagħmilhiex, and other titles, with per-game pages and a contact form.</p>
<h3>Scope</h3>
<ul>
  <li>Brand landing, games listing, and individual game pages.</li>
  <li>Contact form wired to the PHP backend.</li>
</ul>
<h3>Stack</h3>
<ul>
  <li><strong>HTML</strong> / CSS / JavaScript for the site.</li>
  <li><strong>PHP</strong> for the contact API and backend.</li>
</ul>
HTML,
            'full' => '/assets/images/work/reignofplay-logo.png',
            'thumb' => '/assets/images/work/reignofplay-logo.png',
            'gallery' => [
                [
                    'src' => '/assets/images/work/reignofplay-logo.png',
                    'alt' => 'Reign of Play logo',
                ],
            ],
            'links' => [
                ['label' => 'Website', 'url' => 'https://reignofplay.com', 'primary' => true],
            ],
        ],
        'proef-blokzijl' => [
            'slug' => 'proef-blokzijl',
            'title' => 'Proef Blokzijl',
            'summary' => 'WordPress event site from a template — custom child theme, plugins, and WooCommerce ticket sales.',
            'description' => '',
            'body_html' => <<<'HTML'
<p>Website for <strong>Proef Blokzijl</strong>, the culinary walking event organised by Blokzijl hospitality — event info, participating venues, and online tickets.</p>
<h3>Scope</h3>
<ul>
  <li>WordPress site built from a template, with a custom child theme and custom plugins.</li>
  <li><strong>WooCommerce</strong> integration for ticket sales.</li>
</ul>
<h3>Stack</h3>
<ul>
  <li><strong>WordPress</strong> + theme template, custom <strong>child theme</strong>, and custom <strong>plugins</strong>.</li>
  <li><strong>WooCommerce</strong> for tickets / checkout.</li>
</ul>
HTML,
            'full' => '/assets/images/work/proef-blokzijl-001.jpg',
            'thumb' => '/assets/images/work/proef-blokzijl-001.jpg',
            'gallery' => [
                [
                    'src' => '/assets/images/work/proef-blokzijl-001.jpg',
                    'alt' => 'Proef Blokzijl',
                ],
                [
                    'src' => '/assets/images/work/proef-blokzijl-logo.png',
                    'alt' => 'Proef Blokzijl logo',
                ],
            ],
            'links' => [
                ['label' => 'Website', 'url' => 'https://preuvenementproefblokzijl.nl/', 'primary' => true],
            ],
        ],
        'mixta' => [
            'slug' => 'mixta',
            'title' => 'Mixta',
            'summary' => 'WordPress site for a Maltese drama school and production house — custom child theme, plugins, and logo.',
            'description' => '',
            'body_html' => <<<'HTML'
<p>Website for <strong>Mixta</strong> — a drama school and production house in Malta (theatre, TV, film, and community programmes).</p>
<p>I also designed their <strong>logo</strong>.</p>
<h3>Scope</h3>
<ul>
  <li>About, news, school info, and contact.</li>
  <li>Shop and other site sections for the organisation.</li>
  <li>Brand logo design.</li>
</ul>
<h3>Stack</h3>
<ul>
  <li><strong>WordPress</strong> with a custom <strong>child theme</strong> and custom <strong>plugins</strong>.</li>
</ul>
HTML,
            'full' => '/assets/images/work/mixta-001.jpg',
            'thumb' => '/assets/images/work/mixta-001.jpg',
            'gallery' => [
                [
                    'src' => '/assets/images/work/mixta-001.jpg',
                    'alt' => 'Mixta',
                ],
                [
                    'src' => '/assets/images/work/mixta-logo.png',
                    'alt' => 'Mixta logo',
                ],
            ],
            'links' => [
                ['label' => 'Website', 'url' => 'https://mixta.mt/', 'primary' => true],
            ],
        ],
        'aaimalta' => [
            'slug' => 'aaimalta',
            'title' => 'AAIM',
            'summary' => 'WordPress website for the Association of Anaesthesiologists and Intensivists of Malta — custom child theme and plugins.',
            'description' => '',
            'body_html' => <<<'HTML'
<p>Website for <strong>AAIM</strong> — the Association of Anaesthesiologists and Intensivists of Malta: public information on anaesthesia, news and courses, committee, membership, and contact.</p>
<h3>Scope</h3>
<ul>
  <li>Organisation site for members and the public.</li>
  <li>News, courses, public guidance, committee, and join / contact.</li>
</ul>
<h3>Stack</h3>
<ul>
  <li><strong>WordPress</strong> with a custom <strong>child theme</strong> and custom <strong>plugins</strong>.</li>
</ul>
HTML,
            'full' => '/assets/images/work/aaim-website-mockup.png',
            'thumb' => '/assets/images/work/aaim-website-mockup.png',
            'gallery' => [
                [
                    'src' => '/assets/images/work/aaim-website-mockup.png',
                    'alt' => 'AAIM website mockup',
                ],
            ],
            'links' => [
                ['label' => 'Website', 'url' => 'https://aaimalta.com', 'primary' => true],
            ],
        ],
        'suret-il-bniedem' => [
            'slug' => 'suret-il-bniedem',
            'title' => 'Suret il-Bniedem',
            'summary' => 'WordPress website for Fondazzjoni Suret il-Bniedem — custom child theme and plugins.',
            'description' => '',
            'body_html' => <<<'HTML'
<p>Website for <strong>Fondazzjoni Suret il-Bniedem</strong>, a Franciscan foundation in Malta supporting people who are homeless or vulnerable — homes, services, news, vacancies, and donations.</p>
<h3>Scope</h3>
<ul>
  <li>About, services / residential homes, news, vacancies, and contact.</li>
  <li>Donate information and public-facing content for the foundation.</li>
</ul>
<h3>Stack</h3>
<ul>
  <li><strong>WordPress</strong> with a custom <strong>child theme</strong> and custom <strong>plugins</strong>.</li>
</ul>
HTML,
            'full' => '/assets/images/work/sib-website-mockup.png',
            'thumb' => '/assets/images/work/sib-website-mockup.png',
            'gallery' => [
                [
                    'src' => '/assets/images/work/sib-website-mockup.png',
                    'alt' => 'Suret il-Bniedem website mockup',
                ],
            ],
            'links' => [
                ['label' => 'Website', 'url' => 'https://suretilbniedem.com/', 'primary' => true],
            ],
        ],
        'art' => [
            'slug' => 'art',
            'title' => 'Art',
            'summary' => 'Paintings and drawings, plus concrete coffee tables and ornaments.',
            'description' => '',
            'body_html' => <<<'HTML'
<p>Personal art practice: <strong>paintings and drawings</strong>, alongside <strong>concrete coffee tables</strong> and other concrete ornaments.</p>
HTML,
            'full' => '/assets/images/work/art/art-002.jpg',
            'thumb' => '/assets/images/work/art/art-002.jpg',
            'gallery' => [
                ['src' => '/assets/images/work/art/art-001.jpg', 'alt' => 'Drawing'],
                ['src' => '/assets/images/work/art/art-002.jpg', 'alt' => 'Drawing'],
                ['src' => '/assets/images/work/art/art-003.jpg', 'alt' => 'Art work'],
                ['src' => '/assets/images/work/art/art-004.jpg', 'alt' => 'Art work'],
                ['src' => '/assets/images/work/art/art-005.jpg', 'alt' => 'Art work'],
                ['src' => '/assets/images/work/art/art-006.jpg', 'alt' => 'Art work'],
                ['src' => '/assets/images/work/art/art-007.jpg', 'alt' => 'Concrete work'],
                ['src' => '/assets/images/work/art/art-008.jpg', 'alt' => 'Concrete work'],
                ['src' => '/assets/images/work/art/art-009.webp', 'alt' => 'Art work'],
            ],
            'links' => [],
        ],
    ];
}

/**
 * @return array<string, mixed>|null
 */
function portfolio_work_item(string $slug): ?array
{
    $items = portfolio_work_items();
    return $items[$slug] ?? null;
}
