<script lang="ts">
  import AppShell from '#lib/components/layout/AppShell.svelte';
  import type { Snippet } from 'svelte';
  import { APP_ROUTES, type NavItem } from '#lib/config/routes.js';

  const navItems: NavItem[] = [
    { label: 'Orders', href: APP_ROUTES.dealer.orders },
    { label: 'Cart', href: APP_ROUTES.dealer.cart },
    { label: 'Spare Parts', href: APP_ROUTES.dealer.spareParts },
    { label: 'Profile', href: APP_ROUTES.dealer.profile }
  ];

  let { children }: { children: Snippet } = $props();
</script>

<AppShell roleLabel="Dealer Workspace" {navItems}>
  {@render children?.()}
</AppShell>
