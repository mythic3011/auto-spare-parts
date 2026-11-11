<script lang="ts">
  let { roleLabel } = $props<{ roleLabel: string }>();
</script>

<header class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
  <p class="text-xs uppercase tracking-wide text-slate-500">SLMC Portal</p>
  <h1 class="text-lg font-semibold text-slate-900">{roleLabel}</h1>
</header>
