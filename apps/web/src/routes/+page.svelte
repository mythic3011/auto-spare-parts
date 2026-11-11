<script lang="ts">
  import Card from '$lib/components/ui/Card.svelte';
  import Button from '$lib/components/ui/Button.svelte';
  import { APP_ROUTES } from '$lib/config/routes';
</script>

<main class="mx-auto max-w-3xl p-6">
  <Card class="space-y-4">
    <h1 class="text-3xl font-semibold">SLMC Service Lifecycle Management Portal</h1>
    <p class="text-slate-600">
      Access dealer and staff workspaces for orders, parts, reporting, and account operations.
    </p>
    <div class="flex flex-wrap gap-2">
      <Button href={APP_ROUTES.login}>Sign In</Button>
      <Button href={APP_ROUTES.dealer.orders} variant="secondary">Enter Dealer Workspace</Button>
      <Button href={APP_ROUTES.staff.orders} variant="secondary">Enter Staff Workspace</Button>
    </div>
  </Card>
</main>
