<script lang="ts">
  import { onMount } from 'svelte';
  import { createAppApiClient } from '$lib/api/app-client';
  import type { SparePartSummary } from '$lib/api/types';
  import PlaceholderView from '$lib/components/layout/PlaceholderView.svelte';
  import Notice from '$lib/components/ui/Notice.svelte';
  import SparePartsTable from '$lib/components/feature/spare-parts/SparePartsTable.svelte';
  import { APP_ROUTES } from '$lib/config/routes';
  import { createPageResource } from '$lib/stores/page-resource';

  const api = createAppApiClient();
  const state = createPageResource<SparePartSummary[]>();

  const fallbackParts: SparePartSummary[] = [
    {
      partPublicId: 'SP-S-3001',
      sku: 'ST-EL-3311',
      name: 'Headlamp Assembly',
      category: 'Electrical',
      stockQty: 14,
      unitPrice: 630,
      updatedAt: '2026-05-23T05:05:00Z'
    },
    {
      partPublicId: 'SP-S-3002',
      sku: 'ST-EN-2220',
      name: 'Air Filter',
      category: 'Engine',
      stockQty: 60,
      unitPrice: 100,
      updatedAt: '2026-05-23T06:16:00Z'
    }
  ];

  onMount(async () => {
    await state.load(
      () => api.getStaffSpareParts(),
      fallbackParts,
      'Failed to load spare parts'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Staff Spare Parts" description="Loading inventory records..." />
{:else if ($state.data?.length ?? 0) > 0}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <SparePartsTable
      title="Inventory Spare Parts"
      parts={$state.data ?? []}
      actionLabel="Manage"
      actionHrefBuilder={(partPublicId) => `${APP_ROUTES.staff.items}/${partPublicId}/edit`}
    />
  </div>
{:else}
  <PlaceholderView title="Staff Spare Parts" description="No spare parts found." />
{/if}
