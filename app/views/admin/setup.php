<?php /** @var array $wizards */
$schema = settings_schema();
$areas = [];
foreach ($wizards as $key => $w) {
    $areas[$w['area']][$key] = $w;
}
$doneCount = count(array_filter($wizards, fn($w) => ($w['done'])()));
?>
<div class="page-head"><div><h1>Guided setup</h1><p>Connect each service one step at a time. Everything you enter is saved as you go, so you can stop and come back later.</p></div>
    <div class="head-actions"><span class="badge <?= $doneCount === count($wizards) ? 'b-ok' : '' ?>"><?= $doneCount ?> of <?= count($wizards) ?> connected</span></div></div>

<?php foreach ($areas as $area => $list): ?>
    <p class="group-title"><?= e($area) ?></p>
    <div class="wz-grid">
        <?php foreach ($list as $key => $w): $done = ($w['done'])(); ?>
            <button type="button" class="wz-card" data-wz-open="<?= e($key) ?>">
                <span class="wz-ic"><?= icon($w['icon']) ?></span>
                <span class="wz-body"><strong><?= e($w['title']) ?></strong><small><?= e($w['intro']) ?></small></span>
                <span class="wz-meta"><?= $done ? '<span class="badge b-ok">' . icon('check') . ' Connected</span>' : '<span class="badge">' . e($w['time']) . '</span>' ?><span class="wz-go"><?= $done ? 'Review' : 'Start' ?> <?= icon('chevron-right') ?></span></span>
            </button>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php foreach ($wizards as $key => $w): $n = count($w['steps']); ?>
<dialog class="wizard" id="wz-<?= e($key) ?>" data-wizard="<?= e($key) ?>">
    <div class="wz-top">
        <span class="wz-ic"><?= icon($w['icon']) ?></span>
        <div><strong><?= e($w['title']) ?></strong><small data-wz-count>Step 1 of <?= $n ?></small></div>
        <button type="button" class="icon-btn" data-wz-close aria-label="Close"><?= icon('close') ?></button>
    </div>
    <div class="wz-progress"><span style="width:<?= round(100 / $n) ?>%"></span></div>
    <div class="wz-steps">
        <?php foreach ($w['steps'] as $i => $s): ?>
            <section class="wz-step" data-step="<?= $i ?>" <?= $i ? 'hidden' : '' ?>>
                <p class="wz-num">Step <?= $i + 1 ?></p>
                <h2><?= e($s['title']) ?></h2>
                <?php if (!empty($s['text'])): ?><div class="wz-text"><?= $s['text'] ?></div><?php endif; ?>
                <?php foreach ($s['copy'] ?? [] as $label => $value): ?>
                    <div class="wz-copy"><small><?= e($label) ?></small><code><?= e($value) ?></code><button type="button" class="btn secondary sm" data-copy="<?= e($value) ?>"><?= icon('copy') ?> Copy</button></div>
                <?php endforeach; ?>
                <?php if (!empty($s['link'])): ?>
                    <a class="btn secondary wz-link" href="<?= e($s['link'][1]) ?>" <?= str_starts_with($s['link'][1], 'http') ? 'target="_blank" rel="noopener"' : '' ?>><?= e($s['link'][0]) ?> <?= icon('external') ?></a>
                <?php endif; ?>
                <?php foreach ($s['fields'] ?? [] as $group => $keys): ?>
                    <form class="group wz-fields" data-native data-wz-save="/settings/<?= e($group) ?>" data-only="<?= e(implode(',', $keys)) ?>">
                        <?php foreach ($keys as $k): if (!isset($schema[$group]['fields'][$k])) continue; ?><?= setting_input($k, $schema[$group]['fields'][$k]) ?><?php endforeach; ?>
                    </form>
                <?php endforeach; ?>
                <?php if (!empty($s['test'])): ?>
                    <div class="wz-test"><button type="button" class="btn" data-wz-test="<?= e($s['test']) ?>"><?= icon('refresh') ?> Run the check</button><p class="wz-result" data-wz-result hidden></p></div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
    <div class="wz-foot">
        <button type="button" class="btn secondary" data-wz-back hidden><?= icon('arrow-left') ?> Back</button>
        <span class="wz-spacer"></span>
        <button type="button" class="btn" data-wz-next>Next <?= icon('arrow') ?></button>
    </div>
</dialog>
<?php endforeach; ?>
