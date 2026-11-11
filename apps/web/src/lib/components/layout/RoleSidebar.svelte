<script lang="ts">
  import type { NavItem } from '$lib/config/routes';
  import RoleNav from './RoleNav.svelte';

  let { roleLabel, navItems } = $props<{ roleLabel: string; navItems: NavItem[] }>();
</script>

<aside class="border-b border-slate-200 bg-white p-4 md:border-b-0 md:border-r">
  <div class="mb-2 flex items-center gap-2">
    <img src="/images/SLMC_LOGO.png" alt="SLMC Logo" class="h-8 w-8 object-contain dark:hidden" />
    <img src="/images/SLMC_LOGO_DM.png" alt="SLMC Logo" class="hidden h-8 w-8 object-contain dark:block" />
    <div class="text-xl font-bold">SLMC</div>
  </div>
  <div class="mb-4 text-sm text-slate-500">{roleLabel}</div>
  <RoleNav {navItems} />
</aside>
