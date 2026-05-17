<?php

function render_card(string $title, string $body, string $footer = ''): void
{
    ?>
    <div class="rounded-3xl bg-white p-6 shadow-lg">
        <h3 class="text-lg font-bold text-slate-900"><?= e($title) ?></h3>
        <div class="mt-2 text-sm text-slate-600"><?= $body ?></div>
        <?php if ($footer !== ''): ?>
            <div class="mt-4"><?= $footer ?></div>
        <?php endif; ?>
    </div>
    <?php
}
