<script setup lang="ts">
const props = defineProps<{
  isOpen: boolean
  user: any
  totalRegistered?: number
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'logout'): void
}>()

const osNames: Record<string, string> = {
  windows: 'Windows (x64)',
  mac_arm: 'macOS (Apple Silicon M-Series)',
  mac_intel: 'macOS (Intel)',
  linux: 'Linux'
}
</script>

<template>
  <div v-if="isOpen && user" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="emit('close')" />

    <!-- Modal Card -->
    <div class="relative w-full max-w-md rounded-2xl border border-white/10 bg-[#161519]/95 p-6 sm:p-8 shadow-2xl backdrop-blur-2xl text-white z-10">
      
      <!-- Close -->
      <button 
        @click="emit('close')"
        class="absolute top-4 right-4 p-2 text-zinc-400 hover:text-white rounded-lg hover:bg-white/5 transition"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Avatar & Waitlist Badge -->
      <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-rose-600 to-red-900 border border-rose-500/40 text-2xl font-bold text-white mb-3 shadow-[0_0_30px_rgba(225,29,72,0.3)]">
          {{ user.name.charAt(0).toUpperCase() }}
        </div>
        <h3 class="text-xl font-bold text-white">{{ user.name }}</h3>
        <p class="text-xs text-zinc-400">{{ user.email }}</p>

        <!-- Waitlist Rank Banner -->
        <div class="mt-5 p-4 rounded-xl bg-gradient-to-r from-rose-950/40 via-red-900/20 to-black/60 border border-rose-500/30">
          <div class="text-[11px] font-semibold uppercase tracking-wider text-rose-400">Early Access Queue Position</div>
          <div class="text-3xl font-extrabold text-white my-1 flex items-center justify-center gap-1.5">
            <span class="text-rose-500">#</span>{{ user.waitlist_number || '1' }}
          </div>
          <div class="text-[11px] text-zinc-400">
            Status: <span class="text-emerald-400 font-semibold uppercase">{{ user.status === 'invited_to_beta' ? '🎉 Invited to Beta' : 'Reserved (Pending Beta Wave)' }}</span>
          </div>
        </div>
      </div>

      <!-- User Profile Specs -->
      <div class="mt-5 space-y-2.5 text-xs">
        <div class="flex justify-between py-2 border-b border-white/5">
          <span class="text-zinc-400">Profile Type</span>
          <span class="font-medium text-white capitalize">{{ user.user_type === 'student' ? '🎓 University Student' : '💼 Graduate / Professional' }}</span>
        </div>

        <div class="flex justify-between py-2 border-b border-white/5">
          <span class="text-zinc-400">{{ user.user_type === 'student' ? 'University' : 'Alma Mater' }}</span>
          <span class="font-medium text-white text-right max-w-[200px] truncate">{{ user.institution }}</span>
        </div>

        <div class="flex justify-between py-2 border-b border-white/5">
          <span class="text-zinc-400">Major / Specialization</span>
          <span class="font-medium text-white text-right max-w-[200px] truncate">{{ user.faculty_major }}</span>
        </div>

        <div v-if="user.graduation_year" class="flex justify-between py-2 border-b border-white/5">
          <span class="text-zinc-400">{{ user.user_type === 'student' ? 'Graduation Year' : 'Graduated in' }}</span>
          <span class="font-medium text-white">{{ user.graduation_year }}</span>
        </div>

        <div class="flex justify-between py-2 border-b border-white/5">
          <span class="text-zinc-400">Preferred OS</span>
          <span class="font-medium text-rose-400">{{ osNames[user.preferred_os] || user.preferred_os }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-6 flex items-center gap-3">
        <a 
          v-if="user.role === 'admin'"
          href="/admin"
          class="flex-1 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-semibold text-xs text-center transition"
        >
          Admin Portal
        </a>
        <button
          @click="emit('logout')"
          class="flex-1 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white font-medium text-xs transition border border-white/10"
        >
          Sign Out
        </button>
      </div>

    </div>
  </div>
</template>
