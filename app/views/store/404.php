<?php /** @var array $popular */ ?>
<section class="section">
    <div class="wrap narrow center">
        <p class="eyebrow">404</p>
        <h1>This page wandered off</h1>
        <p class="lead">The link may be old or mistyped. Try searching, or start from one of our favourite boxes below.</p>
        <form action="/search/" class="search-inline"><input type="search" name="q" placeholder="Search gift boxes" aria-label="Search"><button class="btn">Search</button></form>
    </div>
</section>
<?php if ($popular): ?>
<section class="section section-tight"><div class="wrap"><div class="grid-products"><?php foreach ($popular as $p): ?><?= render('store/partials/product-card', ['p' => $p]) ?><?php endforeach; ?></div></div></section>
<?php endif; ?>
