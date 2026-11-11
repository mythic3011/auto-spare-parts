<script lang="ts">
  import { onMount } from 'svelte';
  import { createAppApiClient } from '#lib/api/app-client.js';
  import type { SparePartSummary } from '#lib/api/types.js';
  import PlaceholderView from '#lib/components/layout/PlaceholderView.svelte';
  import Notice from '#lib/components/ui/Notice.svelte';
  import SparePartsTable from '#lib/components/feature/spare-parts/SparePartsTable.svelte';
  import { APP_ROUTES } from '#lib/config/routes.js';
  import { createPageResource } from '#lib/stores/page-resource.js';

  const api = createAppApiClient();
  const state = createPageResource<SparePartSummary[]>();

  const fallbackParts: SparePartSummary[] = [
    {
      partPublicId: 'SP-D-1001',
      sku: 'DK-BR-1001',
      name: 'Brake Pad Set',
      category: 'Braking',
      stockQty: 24,
      unitPrice: 210,
      updatedAt: '2026-05-23T03:10:00Z'
    },
    {
      partPublicId: 'SP-D-1002',
      sku: 'DK-FL-4432',
      name: 'Oil Filter',
      category: 'Engine',
      stockQty: 80,
      unitPrice: 55,
      updatedAt: '2026-05-23T04:22:00Z'
    }
  ];

  onMount(async () => {
    await state.load(
      () => api.getDealerSpareParts(),
      fallbackParts,
      'Failed to load spare parts'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Dealer Spare Parts" description="Loading spare parts catalog..." />
{:else if ($state.data?.length ?? 0) > 0}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <SparePartsTable
      title="Available Spare Parts"
      parts={$state.data ?? []}
      actionLabel="Go to Cart"
      actionHrefBuilder={() => APP_ROUTES.dealer.cart}
    />
  </div>
{:else}
  <PlaceholderView title="Dealer Spare Parts" description="No spare parts available." />
{/if}
