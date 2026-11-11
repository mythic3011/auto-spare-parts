<script lang="ts">
  import type { StaffItem } from '#lib/api/types.js';
  import Card from '#lib/components/ui/Card.svelte';
  import StatusBadge from '#lib/components/ui/StatusBadge.svelte';
  import Button from '#lib/components/ui/Button.svelte';

  let { items, editBasePath }: { items: StaffItem[]; editBasePath: string } = $props();

  const money = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
</script>

<Card class="overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="border-b bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 font-medium">SKU</th>
          <th class="px-4 py-3 font-medium">Name</th>
          <th class="px-4 py-3 font-medium">Stock</th>
          <th class="px-4 py-3 font-medium">Reorder Level</th>
          <th class="px-4 py-3 font-medium">Price</th>
          <th class="px-4 py-3 font-medium">Status</th>
          <th class="px-4 py-3 font-medium"></th>
        </tr>
      </thead>
      <tbody>
        {#each items as item}
          <tr class="border-b last:border-b-0">
            <td class="px-4 py-3 font-medium text-slate-900">{item.sku}</td>
            <td class="px-4 py-3 text-slate-900">{item.name}</td>
            <td class="px-4 py-3 text-slate-700">{item.stockQty}</td>
            <td class="px-4 py-3 text-slate-700">{item.reorderLevel}</td>
            <td class="px-4 py-3 text-slate-900">{money(item.unitPrice)}</td>
            <td class="px-4 py-3">
              <StatusBadge status={item.isActive ? 'confirmed' : 'cancelled'} />
            </td>
            <td class="px-4 py-3 text-right">
              <Button href={`${editBasePath}/${item.partPublicId}/edit`} size="sm" variant="ghost">Edit</Button>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</Card>
