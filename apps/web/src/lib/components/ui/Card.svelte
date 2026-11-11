<script lang="ts">
  import { cn } from '$lib/utils/cn';

  let { class: className, children } = $props<{
    class?: string;
    children: import('svelte').Snippet;
  }>();
</script>

<section class={cn('rounded-xl border border-slate-200 bg-white p-5 shadow-sm', className)}>
  {@render children?.()}
</section>
