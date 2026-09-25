<?php /** @var array $page @var bool $isAbout */ ?>
<section class="page-hero<?= $isAbout ? '' : ' compact' ?>">
    <div class="wrap narrow<?= $isAbout ? ' center' : '' ?>"><h1><?= e($page['title']) ?></h1></div>
</section>
<?php if ($isAbout): ?>
    <section class="section section-tight"><div class="wrap about-media reveal"><img src="/assets/img/about.jpg" alt="A Gift Boxx hamper being packed" width="736" height="736" loading="lazy"><img src="/assets/img/hero-2.jpg" alt="Wooden gift box with curated items" width="896" height="1196" loading="lazy"></div></section>
<?php endif; ?>
<section class="section section-tight">
    <div class="wrap narrow rte"><?= $page['content'] ?></div>
    <?php if ($isAbout): ?>
        <div class="wrap narrow center" style="margin-top:2.5rem"><a class="btn btn-lg" href="/shop/">See the collection <?= icon('arrow') ?></a></div>
    <?php endif; ?>
</section>
