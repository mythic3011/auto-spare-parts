<script lang="ts">
  import AppShell from '#lib/components/layout/AppShell.svelte';
  import type { Snippet } from 'svelte';
  import { APP_ROUTES, type NavItem } from '#lib/config/routes.js';

  const navItems: NavItem[] = [
    { label: 'Orders', href: APP_ROUTES.staff.orders },
    { label: 'Items', href: APP_ROUTES.staff.items },
    { label: 'Spare Parts', href: APP_ROUTES.staff.spareParts },
    { label: 'Profile', href: APP_ROUTES.staff.profile },
    { label: 'Reports', href: APP_ROUTES.staff.reports }
  ];

  let { children }: { children: Snippet } = $props();
</script>

<AppShell roleLabel="Staff Workspace" {navItems}>
  {@render children?.()}
</AppShell>
