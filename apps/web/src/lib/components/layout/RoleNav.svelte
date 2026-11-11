<script lang="ts">
  import { page } from '$app/state';
  import { cn } from '#lib/utils/cn.js';
  import type { NavItem } from '#lib/config/routes.js';

  let { navItems }: { navItems: NavItem[] } = $props();

  const isActive = (href: string, pathname: string) =>
    pathname === href || pathname.startsWith(`${href}/`);
</script>

<nav aria-label="Role navigation">
  <ul class="grid gap-2">
    {#each navItems as item}
      <li>
        <a
          class={cn(
            'block rounded-lg border px-3 py-2 text-sm transition',
            isActive(item.href, page.url.pathname)
              ? 'border-blue-300 bg-blue-100 font-semibold text-blue-700'
              : 'border-slate-200 bg-slate-50 text-slate-900 hover:bg-slate-100'
          )}
          href={item.href}
        >
          {item.label}
        </a>
      </li>
    {/each}
  </ul>
</nav>
