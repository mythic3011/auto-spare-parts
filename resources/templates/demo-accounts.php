<?php
require_once __DIR__ . '/../php/class/DemoAccounts.php';
$demoEscape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<button type="button" id="demoAccountsOpen" class="fixed bottom-5 left-5 z-40 rounded-full bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:bg-blue-700" aria-haspopup="dialog" aria-controls="demoAccountsDialog">Try a demo account</button>
<dialog id="demoAccountsDialog" aria-labelledby="demoAccountsTitle" class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 text-gray-900 shadow-xl backdrop:bg-gray-950/60 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
    <div class="flex items-center justify-between gap-4">
        <h2 id="demoAccountsTitle" class="text-xl font-semibold">Demo accounts</h2>
        <button type="button" id="demoAccountsClose" aria-label="Close demo accounts" class="rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800">&times;</button>
    </div>
    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">Use fictional data. Double-click an email or password to copy it.</p>
    <?php foreach (DemoAccounts::USERS as $role => $email): ?>
        <section class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-700" data-demo-account>
            <h3 class="font-semibold"><?= $demoEscape($role) ?></h3>
            <label class="mt-3 block text-xs text-gray-500">Email
                <input readonly data-demo-email value="<?= $demoEscape($email) ?>" title="Double-click to copy email" class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 text-sm dark:border-gray-700 dark:bg-gray-800">
            </label>
            <label class="mt-3 block text-xs text-gray-500">Password
                <input readonly data-demo-password value="<?= $demoEscape(DemoAccounts::PASSWORD) ?>" title="Double-click to copy password" class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 font-mono text-sm dark:border-gray-700 dark:bg-gray-800">
            </label>
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" data-demo-fill class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Use <?= $demoEscape(strtolower($role)) ?></button>
                <button type="button" data-demo-copy class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800">Copy credentials</button>
            </div>
        </section>
    <?php endforeach; ?>
    <p id="demoAccountsStatus" role="status" aria-live="polite" class="mt-4 min-h-5 text-sm text-blue-600 dark:text-blue-400"></p>
</dialog>
<script src="/resources/js/demo-accounts.js" defer></script>
