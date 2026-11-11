<script lang="ts">
  import type { NavItem } from '#lib/config/routes.js';
  import type { Snippet } from 'svelte';
  import RoleSidebar from './RoleSidebar.svelte';
  import RoleTopBar from './RoleTopBar.svelte';

  let { roleLabel, navItems, children }: {
    roleLabel: string;
    navItems: NavItem[];
    children: Snippet;
  } = $props();
</script>

<div class="min-h-screen grid bg-slate-50 text-slate-900 md:grid-cols-[240px_1fr]">
  <RoleSidebar {roleLabel} {navItems} />
  <section class="p-6">
    <RoleTopBar {roleLabel} />
    {@render children?.()}
  </section>
</div>
