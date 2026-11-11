<script lang="ts">
  import { cn } from '#lib/utils/cn.js';

  let { class: className, children }: {
    class?: string;
    children: import('svelte').Snippet;
  } = $props();
</script>

<section class={cn('rounded-xl border border-slate-200 bg-white p-5 shadow-sm', className)}>
  {@render children?.()}
</section>
