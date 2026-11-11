<script lang="ts">
  import { page } from '$app/stores';
  import { onMount } from 'svelte';
  import { createAppApiClient } from '$lib/api/app-client';
  import type { StaffItem } from '$lib/api/types';
  import PlaceholderView from '$lib/components/layout/PlaceholderView.svelte';
  import Notice from '$lib/components/ui/Notice.svelte';
  import ItemFormCard from '$lib/components/feature/items/ItemFormCard.svelte';
  import { createPageResource } from '$lib/stores/page-resource';

  const api = createAppApiClient();
  const state = createPageResource<StaffItem>();

  onMount(async () => {
    const fallbackItem: StaffItem = {
      partPublicId: $page.params.partPublicId,
      sku: 'ST-FB-4400',
      name: 'Fallback Item',
      category: 'General',
      stockQty: 12,
      reorderLevel: 8,
      unitPrice: 320,
      isActive: true,
      updatedAt: '2026-05-23T07:15:00Z'
    };
    await state.load(
      () => api.getStaffItemDetail($page.params.partPublicId),
      fallbackItem,
      'Failed to load item detail'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Staff Edit Item" description="Loading item details..." />
{:else if $state.data}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <ItemFormCard
      title={`Edit Item ${$state.data.partPublicId}`}
      description="Data-backed item edit preview."
      item={$state.data}
    />
  </div>
{:else}
  <PlaceholderView title="Staff Edit Item" description="Item not found." />
{/if}
