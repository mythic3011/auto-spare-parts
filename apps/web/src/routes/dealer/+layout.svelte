<script lang="ts">
  import AppShell from '$lib/components/layout/AppShell.svelte';
  import type { Snippet } from 'svelte';
  import { APP_ROUTES, type NavItem } from '$lib/config/routes';

  const navItems: NavItem[] = [
    { label: 'Orders', href: APP_ROUTES.dealer.orders },
    { label: 'Cart', href: APP_ROUTES.dealer.cart },
    { label: 'Spare Parts', href: APP_ROUTES.dealer.spareParts },
    { label: 'Profile', href: APP_ROUTES.dealer.profile }
  ];

  let { children } = $props<{ children: Snippet }>();
</script>

<AppShell roleLabel="Dealer Workspace" {navItems}>
  {@render children?.()}
</AppShell>
