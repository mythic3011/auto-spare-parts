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
    { orderPublicId: 'D-1001', dealerName: 'North Harbor Dealer', totalAmount: 1230, status: 'processing', createdAt: '2026-05-22T09:30:00Z' },
    { orderPublicId: 'D-1002', dealerName: 'Kowloon Auto Partner', totalAmount: 940, status: 'shipping', createdAt: '2026-05-23T04:15:00Z' }
  ];

  onMount(async () => {
    await state.load(
      () => api.getDealerOrders(),
      fallbackOrders,
      'Failed to load dealer orders'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Dealer Orders" description="Loading order list..." />
{:else if ($state.data?.length ?? 0) > 0}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <OrdersTable orders={$state.data ?? []} detailBasePath={APP_ROUTES.dealer.orders} />
  </div>
{:else}
  <PlaceholderView title="Dealer Orders" description="No orders available." />
{/if}
