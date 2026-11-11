<script lang="ts">
  import '../app.css';
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page } from '$app/stores';
  import { get } from 'svelte/store';
  import { authStore } from '$lib/stores/auth';
  import { resolveGuardRedirect } from '$lib/utils/guard';
  import Notice from '$lib/components/ui/Notice.svelte';
  import { apiConnectionStore } from '$lib/stores/api-connection';

  async function retryApiConnection() {
    await apiConnectionStore.check();
  }

  onMount(() => {
    const check = () => {
      const path = get(page).url.pathname;
      const role = get(authStore).session?.role ?? null;
      const target = resolveGuardRedirect(path, role);
      if (target && target !== path) {
        goto(target, { replaceState: true });
      }
    };

    const unsubscribePage = page.subscribe(check);
    const unsubscribeAuth = authStore.subscribe(check);
    void apiConnectionStore.check();
    check();

    return () => {
      unsubscribePage();
      unsubscribeAuth();
    };
  });
</script>

<svelte:head>
  <title>SLMC Portal</title>
  <link rel="icon" href="/images/SLMC_Icon.png" type="image/png" media="(prefers-color-scheme: light)" />
  <link rel="icon" href="/images/SLMC_Icon_DM.png" type="image/png" media="(prefers-color-scheme: dark)" />
  <link rel="apple-touch-icon" href="/images/SLMC_Icon_login.jpg" />
</svelte:head>

<main>
  {#if $apiConnectionStore.result && !$apiConnectionStore.result.reachable}
    <div class="mx-auto max-w-5xl p-4 pb-0">
      <Notice variant="error">
        <div class="flex flex-wrap items-center gap-2">
          <span>
            Backend API is unreachable. Target: {$apiConnectionStore.result.endpoint}. Check
            <code class="rounded bg-rose-100 px-1 py-0.5 text-xs">VITE_API_ORIGIN</code> or
            <code class="rounded bg-rose-100 px-1 py-0.5 text-xs">VITE_DEV_API_PROXY_TARGET</code>.
          </span>
          <button
            type="button"
            class="rounded border border-rose-300 bg-rose-100 px-2 py-1 text-xs font-medium text-rose-800 hover:bg-rose-200"
            on:click={retryApiConnection}
            disabled={$apiConnectionStore.checking}
          >
            {$apiConnectionStore.checking ? 'Checking...' : 'Retry API check'}
          </button>
        </div>
      </Notice>
    </div>
  {/if}
  <slot />
</main>
