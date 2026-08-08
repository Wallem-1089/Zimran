<?php

declare(strict_types=1);

$bannerData = $safetyBanner['data'] ?? [];
$bannerItems = $bannerData['items'] ?? [];
$safetyBannerUrl = $safetyBannerUrl ?? '#';
?>
<section
    class="card clinical-safety-banner"
    aria-label="Clinical safety information"
    style="border-left:6px solid #b91c1c;background:#fff7ed;"
>
    <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;">
        <div>
            <strong style="color:#991b1b;">Clinical Safety</strong>
            <?php if ($bannerItems === []): ?>
                <p style="margin:.35rem 0 0;">No active structured safety warnings are recorded.</p>
            <?php else: ?>
                <ul style="margin:.5rem 0 0;padding-left:1.25rem;">
                    <?php foreach ($bannerItems as $item): ?>
                        <li style="margin:.25rem 0;">
                            <strong><?= e($item['title']) ?></strong>
                            <?php if (trim((string)($item['detail'] ?? '')) !== ''): ?>
                                — <?= e($item['detail']) ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php if ($safetyBannerUrl !== '#'): ?>
            <a class="btn-secondary" href="<?= e($safetyBannerUrl) ?>">Clinical Safety</a>
        <?php endif; ?>
    </div>
</section>
