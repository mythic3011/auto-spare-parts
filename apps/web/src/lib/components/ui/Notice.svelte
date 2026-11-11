<script lang="ts">
  import { cva, type VariantProps } from 'class-variance-authority';
  import { cn } from '$lib/utils/cn';

  const noticeVariants = cva('rounded-lg border px-3 py-2 text-sm', {
    variants: {
      variant: {
        info: 'border-sky-200 bg-sky-50 text-sky-700',
        warn: 'border-amber-200 bg-amber-50 text-amber-700',
        error: 'border-rose-200 bg-rose-50 text-rose-700'
      }
    },
    defaultVariants: {
      variant: 'info'
    }
  });

  let { variant, class: className, children } = $props<
    VariantProps<typeof noticeVariants> & {
      class?: string;
      children: import('svelte').Snippet;
    }
  >();
</script>

<div class={cn(noticeVariants({ variant }), className)}>
  {@render children?.()}
</div>
