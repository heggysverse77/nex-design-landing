<script setup lang="ts">
import { useHead } from '@unhead/vue'

useHead({ 
  title: 'NexDesign',
  titleTemplate: (title) => (title ? `${title} — Collaborate & Design Without Limits` : 'NexDesign')
})
</script>

<template>
  <RouterView />
</template>
