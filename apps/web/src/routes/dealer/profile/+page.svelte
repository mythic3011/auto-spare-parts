<script lang="ts">
  import { onMount } from 'svelte';
  import { createAppApiClient } from '$lib/api/app-client';
  import type { UserProfile } from '$lib/api/types';
  import PlaceholderView from '$lib/components/layout/PlaceholderView.svelte';
  import Notice from '$lib/components/ui/Notice.svelte';
  import ProfileSummaryCard from '$lib/components/feature/profile/ProfileSummaryCard.svelte';
  import { createPageResource } from '$lib/stores/page-resource';

  const api = createAppApiClient();
  const state = createPageResource<UserProfile>();

  const fallbackProfile: UserProfile = {
    userPublicId: 'dealer-fallback-001',
    displayName: 'Dealer Demo User',
    email: 'dealer.demo@example.com',
    phone: '+852 6111 2233',
    role: 'dealer',
    permissions: ['dealer:spare_parts:read', 'dealer:orders:read', 'dealer:orders:write'],
    lastLoginAt: '2026-05-23T07:20:00Z'
  };

  onMount(async () => {
    await state.load(
      () => api.getDealerProfile(),
      fallbackProfile,
      'Failed to load profile'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Dealer Profile" description="Loading account profile..." />
{:else if $state.data}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <ProfileSummaryCard title="Dealer Profile" profile={$state.data} />
  </div>
{:else}
  <PlaceholderView title="Dealer Profile" description="Profile unavailable." />
{/if}
