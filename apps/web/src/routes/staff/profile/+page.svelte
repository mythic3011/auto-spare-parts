<script lang="ts">
  import { onMount } from 'svelte';
  import { createAppApiClient } from '#lib/api/app-client.js';
  import type { UserProfile } from '#lib/api/types.js';
  import PlaceholderView from '#lib/components/layout/PlaceholderView.svelte';
  import Notice from '#lib/components/ui/Notice.svelte';
  import ProfileSummaryCard from '#lib/components/feature/profile/ProfileSummaryCard.svelte';
  import { createPageResource } from '#lib/stores/page-resource.js';

  const api = createAppApiClient();
  const state = createPageResource<UserProfile>();

  const fallbackProfile: UserProfile = {
    userPublicId: 'staff-fallback-001',
    displayName: 'Staff Demo User',
    email: 'staff.demo@example.com',
    phone: '+852 6333 4455',
    role: 'staff',
    permissions: ['staff:items:read', 'staff:items:write', 'staff:orders:read', 'staff:reports:read'],
    lastLoginAt: '2026-05-23T07:25:00Z'
  };

  onMount(async () => {
    await state.load(
      () => api.getStaffProfile(),
      fallbackProfile,
      'Failed to load profile'
    );
  });
</script>

{#if $state.loading}
  <PlaceholderView title="Staff Profile" description="Loading account profile..." />
{:else if $state.data}
  <div class="space-y-3">
    {#if $state.error}
      <Notice variant="warn">Using fallback data: {$state.error}</Notice>
    {/if}
    <ProfileSummaryCard title="Staff Profile" profile={$state.data} />
  </div>
{:else}
  <PlaceholderView title="Staff Profile" description="Profile unavailable." />
{/if}
