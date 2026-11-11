<script lang="ts">
  import type { SparePartSummary } from '#lib/api/types.js';
  import Card from '#lib/components/ui/Card.svelte';
  import Button from '#lib/components/ui/Button.svelte';

  let {
    title,
    parts,
    actionLabel,
    actionHrefBuilder
  }: {
    title: string;
    parts: SparePartSummary[];
    actionLabel?: string;
    actionHrefBuilder?: (partPublicId: string) => string;
  } = $props();

  const money = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
</script>

<Card class="space-y-3 p-0">
  <div class="px-5 pt-5">
    <h2 class="text-lg font-semibold text-slate-900">{title}</h2>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="border-b bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 font-medium">SKU</th>
          <th class="px-4 py-3 font-medium">Name</th>
          <th class="px-4 py-3 font-medium">Category</th>
          <th class="px-4 py-3 font-medium">Stock</th>
          <th class="px-4 py-3 font-medium">Unit Price</th>
          <th class="px-4 py-3 font-medium">Updated</th>
          {#if actionLabel && actionHrefBuilder}
            <th class="px-4 py-3 font-medium"></th>
          {/if}
        </tr>
      </thead>
      <tbody>
        {#each parts as part}
          <tr class="border-b last:border-b-0">
            <td class="px-4 py-3 font-medium text-slate-900">{part.sku}</td>
            <td class="px-4 py-3 text-slate-900">{part.name}</td>
            <td class="px-4 py-3 text-slate-700">{part.category}</td>
            <td class="px-4 py-3 text-slate-700">{part.stockQty}</td>
            <td class="px-4 py-3 text-slate-900">{money(part.unitPrice)}</td>
            <td class="px-4 py-3 text-slate-700">{new Date(part.updatedAt).toLocaleString()}</td>
            {#if actionLabel && actionHrefBuilder}
              <td class="px-4 py-3 text-right">
                <Button href={actionHrefBuilder(part.partPublicId)} variant="ghost" size="sm">
                  {actionLabel}
                </Button>
              </td>
            {/if}
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</Card>
