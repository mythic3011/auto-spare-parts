<script lang="ts">
  import type { StaffItem } from '#lib/api/types.js';
  import Card from '#lib/components/ui/Card.svelte';
  import Button from '#lib/components/ui/Button.svelte';

  let { title, description, item }: { title: string; description: string; item: StaffItem } = $props();
</script>

<Card class="space-y-4">
  <div>
    <h1 class="text-2xl font-semibold text-slate-900">{title}</h1>
    <p class="text-sm text-slate-600">{description}</p>
  </div>

  <div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-1 text-sm">
      <span class="font-medium text-slate-700">SKU</span>
      <input class="w-full rounded-lg border border-slate-300 px-3 py-2" value={item.sku} readonly />
    </label>
    <label class="space-y-1 text-sm">
      <span class="font-medium text-slate-700">Name</span>
      <input class="w-full rounded-lg border border-slate-300 px-3 py-2" value={item.name} readonly />
    </label>
    <label class="space-y-1 text-sm">
      <span class="font-medium text-slate-700">Category</span>
      <input class="w-full rounded-lg border border-slate-300 px-3 py-2" value={item.category} readonly />
    </label>
    <label class="space-y-1 text-sm">
      <span class="font-medium text-slate-700">Stock Qty</span>
      <input class="w-full rounded-lg border border-slate-300 px-3 py-2" value={String(item.stockQty)} readonly />
    </label>
    <label class="space-y-1 text-sm">
      <span class="font-medium text-slate-700">Reorder Level</span>
      <input class="w-full rounded-lg border border-slate-300 px-3 py-2" value={String(item.reorderLevel)} readonly />
    </label>
    <label class="space-y-1 text-sm">
      <span class="font-medium text-slate-700">Unit Price</span>
      <input class="w-full rounded-lg border border-slate-300 px-3 py-2" value={String(item.unitPrice)} readonly />
    </label>
  </div>

  <p class="text-xs text-slate-500">This is a data-driven preview surface for the staff item workflow.</p>
  <div class="flex justify-end">
    <Button variant="secondary" disabled>Save (coming soon)</Button>
  </div>
</Card>
