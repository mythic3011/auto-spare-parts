<script lang="ts">
  import { cn } from '#lib/utils/cn.js';
  import type { OrderStatus } from '#lib/api/types.js';

  let { status }: { status: OrderStatus } = $props();

  const statusClassMap: Record<OrderStatus, string> = {
    pending: 'bg-amber-100 text-amber-800 border-amber-200',
    confirmed: 'bg-sky-100 text-sky-800 border-sky-200',
    processing: 'bg-indigo-100 text-indigo-800 border-indigo-200',
    shipping: 'bg-violet-100 text-violet-800 border-violet-200',
    shipped: 'bg-emerald-100 text-emerald-800 border-emerald-200',
    cancelled: 'bg-rose-100 text-rose-800 border-rose-200'
  };
</script>

<span
  class={cn(
    'inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold capitalize',
    statusClassMap[status]
  )}
>
  {status}
</span>
