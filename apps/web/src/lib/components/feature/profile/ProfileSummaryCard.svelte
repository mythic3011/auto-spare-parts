<script lang="ts">
  import type { UserProfile } from '$lib/api/types';
  import Card from '$lib/components/ui/Card.svelte';

  let { title, profile } = $props<{ title: string; profile: UserProfile }>();
</script>

<Card class="space-y-4">
  <div>
    <h1 class="text-2xl font-semibold text-slate-900">{title}</h1>
    <p class="text-sm text-slate-600">Account summary and permission scope.</p>
  </div>

  <div class="grid gap-2 text-sm text-slate-700 md:grid-cols-2">
    <p><span class="font-medium text-slate-900">Name:</span> {profile.displayName}</p>
    <p><span class="font-medium text-slate-900">Role:</span> {profile.role}</p>
    <p><span class="font-medium text-slate-900">Email:</span> {profile.email}</p>
    <p><span class="font-medium text-slate-900">Phone:</span> {profile.phone}</p>
    <p class="md:col-span-2"><span class="font-medium text-slate-900">User ID:</span> {profile.userPublicId}</p>
    {#if profile.lastLoginAt}
      <p class="md:col-span-2"><span class="font-medium text-slate-900">Last Login:</span> {new Date(profile.lastLoginAt).toLocaleString()}</p>
    {/if}
  </div>

  <div class="space-y-2">
    <h2 class="text-sm font-semibold text-slate-900">Permissions</h2>
    <ul class="flex flex-wrap gap-2">
      {#each profile.permissions as permission}
        <li class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700">{permission}</li>
      {/each}
    </ul>
  </div>
</Card>
