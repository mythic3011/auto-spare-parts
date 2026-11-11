<script lang="ts">
  import { goto } from '$app/navigation';
  import { onMount } from 'svelte';
  import { get } from 'svelte/store';
  import { createAppApiClient } from '#lib/api/app-client.js';
  import type { LoginRequest } from '#lib/api/types.js';
  import Button from '#lib/components/ui/Button.svelte';
  import Card from '#lib/components/ui/Card.svelte';
  import Notice from '#lib/components/ui/Notice.svelte';
  import { APP_ROUTES } from '#lib/config/routes.js';
  import { authStore } from '#lib/stores/auth.js';
  import { defaultRouteForRole } from '#lib/utils/auth-routing.js';

  const api = createAppApiClient();
  let username = '';
  let password = '';
  let loading = false;
  let error = '';

  const demoAccounts: Record<'dealer' | 'staff', LoginRequest> = {
    dealer: {
      email: 'dealer@example.com',
      password: 'password'
    },
    staff: {
      email: 'staff@example.com',
      password: 'password'
    }
  };

  async function quickFill(role: 'dealer' | 'staff') {
    username = demoAccounts[role].email;
    password = demoAccounts[role].password;
    await submit();
  }

  async function submit() {
    if (loading) {
      return;
    }
    error = '';
    loading = true;
    try {
      const session = await api.login({
        email: username.trim(),
        password
      });
      authStore.login(session);
      await goto(defaultRouteForRole(session.role));
    } catch (err) {
      error = err instanceof Error ? err.message : 'Login failed';
    } finally {
      loading = false;
    }
  }

  onMount(() => {
    const current = get(authStore).session;
    if (current) {
      goto(defaultRouteForRole(current.role));
    }
  });
</script>

<div class="mx-auto flex min-h-screen max-w-md items-center px-4">
  <Card class="w-full">
    <h1 class="text-2xl font-semibold text-slate-900">SLMC Portal Login</h1>
    <p class="mt-1 text-sm text-slate-600">Sign in to your dealer or staff workspace.</p>

    <form
      class="mt-5 space-y-3"
      on:submit|preventDefault={submit}
    >
      <label class="block text-sm font-medium text-slate-700" for="username">Email / Username</label>
      <input
        id="username"
        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
        bind:value={username}
        autocomplete="username"
        required
      />

      <label class="block text-sm font-medium text-slate-700" for="password">Password</label>
      <input
        id="password"
        type="password"
        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
        bind:value={password}
        autocomplete="current-password"
        required
      />

      {#if error}
        <Notice variant="error">{error}</Notice>
      {/if}

      <div class="flex gap-2">
        <Button type="submit" disabled={loading}>{loading ? 'Signing in...' : 'Sign in'}</Button>
        <Button href={APP_ROUTES.home} variant="ghost">Back</Button>
      </div>
    </form>

    <div class="mt-4 border-t border-slate-100 pt-3">
      <p class="text-xs text-slate-500">Development helpers</p>
      <div class="mt-2 flex gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
          on:click={() => quickFill('dealer')}
        >
          Use dealer demo
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
          on:click={() => quickFill('staff')}
        >
          Use staff demo
        </button>
      </div>
    </div>
  </Card>
</div>
