<script lang="ts">
  import type { OrderDetail } from '$lib/api/types';
  import Card from '$lib/components/ui/Card.svelte';
  import StatusBadge from '$lib/components/ui/StatusBadge.svelte';

  let { order } = $props<{ order: OrderDetail }>();

  const money = (amount: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
</script>

<div class="grid gap-4">
  <Card class="space-y-3">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">Order {order.orderPublicId}</h2>
        <p class="text-sm text-slate-600">Created {new Date(order.createdAt).toLocaleString()}</p>
      </div>
      <StatusBadge status={order.status} />
    </div>
    <div class="grid gap-2 text-sm text-slate-700 md:grid-cols-2">
      <p><span class="font-medium text-slate-900">Dealer:</span> {order.dealerName}</p>
      <p><span class="font-medium text-slate-900">Contact:</span> {order.contactName} ({order.contactPhone})</p>
      <p class="md:col-span-2"><span class="font-medium text-slate-900">Shipping:</span> {order.shippingAddress}</p>
    </div>
  </Card>

  <Card class="space-y-2">
    <h3 class="text-base font-semibold">Items</h3>
    <ul class="grid gap-2">
      {#each order.items as item}
        <li class="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm">
          <div>
            <p class="font-medium text-slate-900">{item.name}</p>
            <p class="text-slate-600">{item.sku} · Qty {item.quantity}</p>
          </div>
          <p class="font-medium text-slate-900">{money(item.unitPrice * item.quantity)}</p>
        </li>
      {/each}
    </ul>
    <div class="flex justify-end pt-2 text-sm">
      <p class="font-semibold text-slate-900">Total: {money(order.totalAmount)}</p>
    </div>
  </Card>
</div>
