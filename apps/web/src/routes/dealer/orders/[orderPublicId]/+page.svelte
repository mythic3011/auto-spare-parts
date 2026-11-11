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
      totalAmount: 1250,
      status: 'processing',
      createdAt: '2026-05-23T02:30:00Z',
      shippingAddress: '12 Harbour Rd, Wan Chai, Hong Kong',
      contactName: 'Alex Chan',
      contactPhone: '+852 9123 4567',
      items: [
        { sku: 'SP-1001', name: 'Brake Pad Set', quantity: 2, unitPrice: 210 },
        { sku: 'SP-2044', name: 'Oil Filter', quantity: 4, unitPrice: 55 }
      ]
    };
    await state.load(
      () => api.getDealerOrderDetail($page.params.orderPublicId),
      fallbackOrder,
      'Failed to load order detail'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Dealer Order Detail" description="Loading order details..." />
{:else if $state.data}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <OrderDetailCard order={$state.data} />
  </div>
{:else}
  <PlaceholderView title="Dealer Order Detail" description="Order not found." />
{/if}
