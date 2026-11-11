<script lang="ts">
  import { cva, type VariantProps } from 'class-variance-authority';
  import { cn } from '$lib/utils/cn';

  const buttonVariants = cva(
    'inline-flex items-center justify-center rounded-lg text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:pointer-events-none disabled:opacity-50',
    {
      variants: {
        variant: {
          primary: 'bg-blue-600 text-white hover:bg-blue-700',
          secondary: 'bg-slate-100 text-slate-900 hover:bg-slate-200',
          ghost: 'bg-transparent text-slate-700 hover:bg-slate-100'
        },
        size: {
          sm: 'h-8 px-3',
          md: 'h-10 px-4',
          lg: 'h-11 px-5'
        }
      },
      defaultVariants: {
        variant: 'primary',
        size: 'md'
      }
    }
  );

  type Props = VariantProps<typeof buttonVariants> & {
    href?: string;
    class?: string;
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    children: import('svelte').Snippet;
  };

  let { href, class: className, variant, size, type = 'button', disabled = false, children }: Props =
    $props();
</script>

{#if href}
  <a class={cn(buttonVariants({ variant, size }), className)} href={href} aria-disabled={disabled}>
    {@render children?.()}
  </a>
{:else}
  <button class={cn(buttonVariants({ variant, size }), className)} {type} {disabled}>
    {@render children?.()}
  </button>
{/if}
