<script lang="ts">
  import type { OrderSummary } from '#lib/api/types.js';
  import StatusBadge from '#lib/components/ui/StatusBadge.svelte';
  import Card from '#lib/components/ui/Card.svelte';

  let { orders, detailBasePath }: { orders: OrderSummary[]; detailBasePath: string } = $props();

  const money = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
</script>

<Card class="overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="border-b bg-slate-50 text-slate-600">
        <tr>
          <th class="px-4 py-3 font-medium">Order</th>
          <th class="px-4 py-3 font-medium">Dealer</th>
          <th class="px-4 py-3 font-medium">Created</th>
          <th class="px-4 py-3 font-medium">Total</th>
          <th class="px-4 py-3 font-medium">Status</th>
          <th class="px-4 py-3 font-medium"></th>
        </tr>
      </thead>
      <tbody>
        {#each orders as order}
          <tr class="border-b last:border-b-0">
            <td class="px-4 py-3 font-medium text-slate-900">{order.orderPublicId}</td>
            <td class="px-4 py-3 text-slate-700">{order.dealerName}</td>
            <td class="px-4 py-3 text-slate-700">{new Date(order.createdAt).toLocaleString()}</td>
            <td class="px-4 py-3 text-slate-900">{money(order.totalAmount)}</td>
            <td class="px-4 py-3"><StatusBadge status={order.status} /></td>
            <td class="px-4 py-3 text-right">
              <a class="text-sm font-medium text-blue-600 hover:text-blue-700" href={`${detailBasePath}/${order.orderPublicId}`}>
                View
              </a>
            </td>
          </tr>
        {/each}
      </tbody>
    </table>
  </div>
</Card>
