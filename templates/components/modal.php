<?php

function render_modal(string $title, string $content, string $id): void
{
    ?>
    <div x-data="{ open: false }" id="<?= e($id) ?>">
        <button type="button" @click="open = true" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700">Open</button>
        <div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-slate-900/60 p-4">
            <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-xl font-bold text-slate-900"><?= e($title) ?></h3>
                    <button type="button" @click="open = false" class="text-slate-500">Close</button>
                </div>
                <div class="mt-4 text-sm text-slate-600"><?= $content ?></div>
            </div>
        </div>
    </div>
    <?php
}
