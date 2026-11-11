<script lang="ts">
  import { onMount } from 'svelte';
  import { createAppApiClient } from '#lib/api/app-client.js';
  import type { StaffItem } from '#lib/api/types.js';
  import PlaceholderView from '#lib/components/layout/PlaceholderView.svelte';
  import Notice from '#lib/components/ui/Notice.svelte';
  import ItemsTable from '#lib/components/feature/items/ItemsTable.svelte';
  import Button from '#lib/components/ui/Button.svelte';
  import { APP_ROUTES } from '#lib/config/routes.js';
  import { createPageResource } from '#lib/stores/page-resource.js';

  const api = createAppApiClient();
  const state = createPageResource<StaffItem[]>();

  const fallbackItems: StaffItem[] = [
    {
      partPublicId: 'IT-5001',
      sku: 'ST-BR-9112',
      name: 'Front Brake Rotor',
      category: 'Braking',
      stockQty: 18,
      reorderLevel: 10,
      unitPrice: 460,
      isActive: true,
      updatedAt: '2026-05-23T06:45:00Z'
    },
    {
      partPublicId: 'IT-5002',
      sku: 'ST-EN-5551',
      name: 'Spark Plug Kit',
      category: 'Engine',
      stockQty: 7,
      reorderLevel: 12,
      unitPrice: 180,
      isActive: false,
      updatedAt: '2026-05-23T06:55:00Z'
    }
  ];

  onMount(async () => {
    await state.load(
      () => api.getStaffItems(),
      fallbackItems,
      'Failed to load items'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Staff Items" description="Loading item catalog..." />
{:else if ($state.data?.length ?? 0) > 0}
  <div class="space-y-3">
    <div class="flex justify-end">
      <Button href={APP_ROUTES.staff.newItem} size="sm">New Item</Button>
    </div>
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <ItemsTable items={$state.data ?? []} editBasePath={APP_ROUTES.staff.items} />
  </div>
{:else}
  <PlaceholderView title="Staff Items" description="No inventory items available." />
{/if}
