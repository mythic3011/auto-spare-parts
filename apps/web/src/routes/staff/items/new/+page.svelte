<script lang="ts">
  import { onMount } from 'svelte';
  import { createAppApiClient } from '$lib/api/app-client';
  import type { StaffItem } from '$lib/api/types';
  import PlaceholderView from '$lib/components/layout/PlaceholderView.svelte';
  import Notice from '$lib/components/ui/Notice.svelte';
  import ItemFormCard from '$lib/components/feature/items/ItemFormCard.svelte';
  import { createPageResource } from '$lib/stores/page-resource';

  const api = createAppApiClient();
  const state = createPageResource<StaffItem>();

  const fallbackTemplate: StaffItem = {
    partPublicId: 'NEW-TEMPLATE',
    sku: 'ST-NEW-0001',
    name: 'New Spare Part',
    category: 'General',
    stockQty: 0,
    reorderLevel: 8,
    unitPrice: 0,
    isActive: true,
    updatedAt: '2026-05-23T07:00:00Z'
  };

  onMount(async () => {
    await state.load(
      async () => {
        const items = await api.getStaffItems();
        return items[0]
          ? {
              ...items[0],
              partPublicId: 'NEW-TEMPLATE',
              sku: 'ST-NEW-0001',
              name: 'New Spare Part',
              stockQty: 0,
              unitPrice: 0
            }
          : fallbackTemplate;
      },
      fallbackTemplate,
      'Failed to load item template'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Staff New Item" description="Preparing item form..." />
{:else if $state.data}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <ItemFormCard
      title="Create Item"
      description="Data-backed draft for item creation workflow."
      item={$state.data}
    />
  </div>
{:else}
  <PlaceholderView title="Staff New Item" description="Unable to prepare item form." />
{/if}
