<script lang="ts">
  import Card from '$lib/components/ui/Card.svelte';
  let { title, description } = $props<{ title: string; description: string }>();
</script>

<Card class="space-y-2">
  <h1 class="text-2xl font-semibold">{title}</h1>
  <p class="text-slate-600">{description}</p>
</Card>
