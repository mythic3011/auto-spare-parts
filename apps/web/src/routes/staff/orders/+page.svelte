<script lang="ts">
  import { onMount } from 'svelte';
  import OrdersTable from '$lib/components/feature/orders/OrdersTable.svelte';
  import PlaceholderView from '$lib/components/layout/PlaceholderView.svelte';
  import Notice from '$lib/components/ui/Notice.svelte';
  import { createAppApiClient } from '$lib/api/app-client';
  import type { OrderSummary } from '$lib/api/types';
  import { APP_ROUTES } from '$lib/config/routes';
  import { createPageResource } from '$lib/stores/page-resource';

  const api = createAppApiClient();
  const state = createPageResource<OrderSummary[]>();

  const fallbackOrders: OrderSummary[] = [
    { orderPublicId: 'S-3001', dealerName: 'Central Showroom', totalAmount: 1880, status: 'pending', createdAt: '2026-05-23T01:20:00Z' },
    { orderPublicId: 'S-3002', dealerName: 'Island Car Hub', totalAmount: 720, status: 'confirmed', createdAt: '2026-05-23T05:50:00Z' }
  ];

  onMount(async () => {
    await state.load(
      () => api.getStaffOrders(),
      fallbackOrders,
      'Failed to load staff orders'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Staff Orders" description="Loading order queue..." />
{:else if ($state.data?.length ?? 0) > 0}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <OrdersTable orders={$state.data ?? []} detailBasePath={APP_ROUTES.staff.orders} />
  </div>
{:else}
  <PlaceholderView title="Staff Orders" description="No orders available." />
{/if}
