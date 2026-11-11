<script lang="ts">
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import OrderDetailCard from '$lib/components/feature/orders/OrderDetailCard.svelte';
  import { createAppApiClient } from '$lib/api/app-client';
  import type { OrderDetail } from '$lib/api/types';
  import PlaceholderView from '$lib/components/layout/PlaceholderView.svelte';
  import Notice from '$lib/components/ui/Notice.svelte';
  import { createPageResource } from '$lib/stores/page-resource';

  const api = createAppApiClient();
  const state = createPageResource<OrderDetail>();

  onMount(async () => {
    const fallbackOrder: OrderDetail = {
      orderPublicId: $page.params.orderPublicId,
      dealerName: 'Fallback Dealer',
      totalAmount: 930,
      status: 'pending',
      createdAt: '2026-05-23T06:05:00Z',
      shippingAddress: '88 Nathan Rd, Tsim Sha Tsui, Hong Kong',
      contactName: 'Chris Wong',
      contactPhone: '+852 6777 4433',
      items: [
        { sku: 'SP-8880', name: 'Headlamp Assembly', quantity: 1, unitPrice: 630 },
        { sku: 'SP-1115', name: 'Air Filter', quantity: 3, unitPrice: 100 }
      ]
    };
    await state.load(
      () => api.getStaffOrderDetail($page.params.orderPublicId),
      fallbackOrder,
      'Failed to load order detail'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Staff Order Detail" description="Loading order details..." />
{:else if $state.data}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <OrderDetailCard order={$state.data} />
  </div>
{:else}
  <PlaceholderView title="Staff Order Detail" description="Order not found." />
{/if}
