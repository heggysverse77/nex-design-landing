<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import NexLogo from '@/components/landing/NexLogo.vue'

const authenticated = ref(false)
const adminUser = ref<any>(null)
const loading = ref(false)
const errorMsg = ref('')

// Admin login form
const loginForm = reactive({
  email: '',
  password: ''
})

// Stats
const stats = ref<any>({
  total_users: 0,
  students_count: 0,
  graduates_count: 0,
  professionals_count: 0,
  os_breakdown: [],
  top_institutions: [],
  top_majors: []
})

// Users list & filters
const users = ref<any[]>([])
const pagination = ref({
  current_page: 1,
  limit: 25,
  total_records: 0,
  total_pages: 1
})

const filters = reactive({
  search: '',
  user_type: '',
  preferred_os: '',
  status: ''
})

const osLabels: Record<string, string> = {
  windows: 'Windows (x64)',
  mac_arm: 'macOS (Apple Silicon)',
  mac_intel: 'macOS (Intel)',
  linux: 'Linux (x64)'
}

async function checkAdminAuth() {
  try {
    const res = await fetch('/api/auth/me.php')
    const data = await res.json()
    if (data.success && data.data?.user?.role === 'admin') {
      authenticated.value = true
      adminUser.value = data.data.user
      fetchStats()
      fetchUsers()
    } else {
      authenticated.value = false
    }
  } catch (err) {
    authenticated.value = false
  }
}

async function handleAdminLogin() {
  errorMsg.value = ''
  loading.value = true
  try {
    const res = await fetch('/api/auth/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(loginForm)
    })
    const data = await res.json()
    if (!data.success) {
      errorMsg.value = data.error || 'Authentication failed.'
    } else if (data.data?.user?.role !== 'admin') {
      errorMsg.value = 'Access denied: Administrator privileges required.'
    } else {
      authenticated.value = true
      adminUser.value = data.data.user
      fetchStats()
      fetchUsers()
    }
  } catch (err) {
    errorMsg.value = 'Network error while connecting to server.'
  } finally {
    loading.value = false
  }
}

async function fetchStats() {
  try {
    const res = await fetch('/api/admin/stats.php')
    const data = await res.json()
    if (data.success) {
      stats.value = data.data
    }
  } catch (err) {
    console.error('Failed to load stats', err)
  }
}

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      search: filters.search,
      user_type: filters.user_type,
      preferred_os: filters.preferred_os,
      status: filters.status
    })
    const res = await fetch(`/api/admin/users.php?${params.toString()}`)
    const data = await res.json()
    if (data.success) {
      users.value = data.data.users
      pagination.value = data.data.pagination
    }
  } catch (err) {
    console.error('Failed to load users', err)
  } finally {
    loading.value = false
  }
}

async function updateStatus(userId: number, newStatus: string) {
  try {
    const res = await fetch('/api/admin/update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId, status: newStatus })
    })
    const data = await res.json()
    if (data.success) {
      const u = users.value.find(item => item.id === userId)
      if (u) u.status = newStatus
    }
  } catch (err) {
    alert('Failed to update status')
  }
}

function exportCsv() {
  window.open('/api/admin/export_csv.php', '_blank')
}

async function handleLogout() {
  await fetch('/api/auth/logout.php')
  authenticated.value = false
  adminUser.value = null
}

onMounted(() => {
  checkAdminAuth()
})
</script>

<template>
  <div class="min-h-screen bg-[#070709] text-zinc-100 p-4 sm:p-8 font-sans selection:bg-rose-600 selection:text-white">
    
    <!-- LOGIN SCREEN FOR ADMIN -->
    <div v-if="!authenticated" class="max-w-md mx-auto mt-20 p-8 rounded-2xl border border-white/10 bg-[#121115]/90 shadow-2xl backdrop-blur-2xl">
      <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-zinc-900 border border-white/10 text-rose-400 mb-3 shadow-inner">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold text-white tracking-tight">Nex Admin Portal</h2>
        <p class="text-xs text-zinc-400 font-mono mt-1">Authenticate to manage pre-release accounts</p>
      </div>

      <div v-if="errorMsg" class="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
        {{ errorMsg }}
      </div>

      <form @submit.prevent="handleAdminLogin" class="space-y-4">
        <div>
          <label class="block text-[11px] font-mono uppercase tracking-wider text-zinc-400 mb-1">Admin Email</label>
          <input
            v-model="loginForm.email"
            type="email"
            required
            placeholder="admin@nex-design.online"
            class="w-full px-3.5 py-2.5 rounded-xl bg-black/40 border border-white/10 text-white text-xs focus:outline-none focus:border-rose-500"
          />
        </div>

        <div>
          <label class="block text-[11px] font-mono uppercase tracking-wider text-zinc-400 mb-1">Password</label>
          <input
            v-model="loginForm.password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full px-3.5 py-2.5 rounded-xl bg-black/40 border border-white/10 text-white text-xs focus:outline-none focus:border-rose-500"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full py-3 rounded-xl bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-500 hover:to-red-600 text-white font-mono font-bold text-xs tracking-wider shadow-lg disabled:opacity-50 transition cursor-pointer"
        >
          {{ loading ? 'VERIFYING...' : 'ACCESS CONTROL PANEL' }}
        </button>
      </form>

      <div class="mt-6 text-center">
        <router-link to="/" class="text-xs font-mono text-zinc-400 hover:text-white transition">← Return to Studio</router-link>
      </div>
    </div>

    <!-- MAIN ADMIN DASHBOARD -->
    <div v-else class="max-w-7xl mx-auto space-y-8">
      
      <!-- Top Navbar -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-white/10">
        <div>
          <div class="flex items-center gap-3">
            <span class="text-2xl font-black bg-gradient-to-r from-rose-400 to-red-500 bg-clip-text text-transparent font-sans">NEX</span>
            <span class="text-[10px] font-mono px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20 font-bold uppercase tracking-widest">Pre-Release Control</span>
          </div>
          <h1 class="text-xl font-bold text-white mt-1">Early Access Accounts</h1>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="exportCsv"
            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-mono font-bold flex items-center gap-2 transition shadow-lg shadow-emerald-950/30 cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            EXPORT CSV
          </button>

          <router-link to="/" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 text-xs font-mono font-medium border border-white/10 transition">
            LIVE STUDIO
          </router-link>

          <button
            @click="handleLogout"
            class="px-3 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 text-xs font-mono font-medium border border-rose-500/20 transition cursor-pointer"
          >
            LOGOUT
          </button>
        </div>
      </div>

      <!-- KPI METRIC CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10">
          <div class="text-[11px] font-mono uppercase tracking-wider text-zinc-400">Total Registered</div>
          <div class="text-3xl font-extrabold text-white font-mono mt-2">{{ stats.total_users }}</div>
          <div class="text-[10px] text-zinc-500 font-mono mt-1">Queue entries</div>
        </div>

        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10">
          <div class="text-[11px] font-mono uppercase tracking-wider text-rose-400">University Students</div>
          <div class="text-3xl font-extrabold text-white font-mono mt-2">{{ stats.students_count }}</div>
          <div class="text-[10px] text-zinc-500 font-mono mt-1">
            {{ stats.total_users > 0 ? Math.round((stats.students_count / stats.total_users) * 100) : 0 }}% of total
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10">
          <div class="text-[11px] font-mono uppercase tracking-wider text-blue-400">Graduates & Pros</div>
          <div class="text-3xl font-extrabold text-white font-mono mt-2">{{ stats.graduates_count + stats.professionals_count }}</div>
          <div class="text-[10px] text-zinc-500 font-mono mt-1">
            {{ stats.total_users > 0 ? Math.round(((stats.graduates_count + stats.professionals_count) / stats.total_users) * 100) : 0 }}% of total
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-zinc-900/60 border border-white/10">
          <div class="text-[11px] font-mono uppercase tracking-wider text-purple-400">Top Platform</div>
          <div class="text-2xl font-bold text-white font-mono mt-2 capitalize">
            {{ osLabels[stats.os_breakdown?.[0]?.preferred_os] || stats.os_breakdown?.[0]?.preferred_os || 'Windows' }}
          </div>
          <div class="text-[10px] text-zinc-500 font-mono mt-1">
            {{ stats.os_breakdown?.[0]?.count || 0 }} requests
          </div>
        </div>
      </div>

      <!-- ANALYTICS HIGHLIGHTS -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Top Universities -->
        <div class="p-6 rounded-2xl bg-zinc-900/40 border border-white/10">
          <h3 class="text-xs font-mono uppercase tracking-wider font-bold text-white mb-4 flex items-center gap-2">
            <span>Institutions & Universities</span>
          </h3>
          <div class="space-y-3">
            <div v-for="inst in stats.top_institutions" :key="inst.institution" class="flex items-center justify-between text-xs py-1.5 border-b border-white/5">
              <span class="text-zinc-300 font-medium truncate max-w-[280px]">{{ inst.institution }}</span>
              <span class="px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-400 font-mono font-bold border border-rose-500/20 text-[11px]">{{ inst.count }} users</span>
            </div>
            <div v-if="!stats.top_institutions?.length" class="text-xs text-zinc-500 italic">No institution data recorded.</div>
          </div>
        </div>

        <!-- Top Majors -->
        <div class="p-6 rounded-2xl bg-zinc-900/40 border border-white/10">
          <h3 class="text-xs font-mono uppercase tracking-wider font-bold text-white mb-4 flex items-center gap-2">
            <span>Specializations & Faculties</span>
          </h3>
          <div class="space-y-3">
            <div v-for="maj in stats.top_majors" :key="maj.faculty_major" class="flex items-center justify-between text-xs py-1.5 border-b border-white/5">
              <span class="text-zinc-300 font-medium truncate max-w-[280px]">{{ maj.faculty_major }}</span>
              <span class="px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-400 font-mono font-bold border border-blue-500/20 text-[11px]">{{ maj.count }} users</span>
            </div>
            <div v-if="!stats.top_majors?.length" class="text-xs text-zinc-500 italic">No specialization data recorded.</div>
          </div>
        </div>
      </div>

      <!-- FILTER & SEARCH BAR -->
      <div class="p-4 rounded-2xl bg-zinc-900/80 border border-white/10 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="relative w-full md:w-80">
          <input
            v-model="filters.search"
            @input="fetchUsers(1)"
            type="text"
            placeholder="Search name, email, university..."
            class="w-full pl-9 pr-4 py-2 rounded-xl bg-black/50 border border-white/10 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-rose-500"
          />
          <svg class="w-4 h-4 text-zinc-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
          <select
            v-model="filters.user_type"
            @change="fetchUsers(1)"
            class="px-3 py-2 rounded-xl bg-black/50 border border-white/10 text-xs text-zinc-300 focus:outline-none focus:border-rose-500"
          >
            <option value="">All Categories</option>
            <option value="student">University Students</option>
            <option value="graduate">Graduates & Pros</option>
          </select>

          <select
            v-model="filters.preferred_os"
            @change="fetchUsers(1)"
            class="px-3 py-2 rounded-xl bg-black/50 border border-white/10 text-xs text-zinc-300 focus:outline-none focus:border-rose-500"
          >
            <option value="">All Platforms</option>
            <option value="windows">Windows</option>
            <option value="mac_arm">macOS (Apple Silicon)</option>
            <option value="mac_intel">macOS (Intel)</option>
            <option value="linux">Linux</option>
          </select>

          <select
            v-model="filters.status"
            @change="fetchUsers(1)"
            class="px-3 py-2 rounded-xl bg-black/50 border border-white/10 text-xs text-zinc-300 focus:outline-none focus:border-rose-500"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="invited_to_beta">Invited to Beta</option>
            <option value="approved">Approved</option>
          </select>
        </div>
      </div>

      <!-- USERS DATA TABLE -->
      <div class="rounded-2xl border border-white/10 bg-zinc-900/40 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-black/60 text-zinc-400 uppercase font-mono tracking-wider text-[10px] border-b border-white/10">
              <tr>
                <th class="py-3 px-4">Queue #</th>
                <th class="py-3 px-4">User</th>
                <th class="py-3 px-4">Category</th>
                <th class="py-3 px-4">Institution & Major</th>
                <th class="py-3 px-4">Year</th>
                <th class="py-3 px-4">Platform</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Date</th>
                <th class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-zinc-300">
              <tr v-for="u in users" :key="u.id" class="hover:bg-white/[0.02] transition">
                <td class="py-3.5 px-4 font-mono font-bold text-rose-400">
                  #{{ u.waitlist_number || u.id }}
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-semibold text-white">{{ u.name }}</div>
                  <div class="text-[11px] text-zinc-400 font-mono">{{ u.email }}</div>
                  <a v-if="u.portfolio_url" :href="u.portfolio_url" target="_blank" class="text-[10px] text-rose-400 hover:underline inline-block mt-0.5 font-mono">
                    Portfolio Link
                  </a>
                </td>
                <td class="py-3.5 px-4">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border',
                      u.user_type === 'student' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20'
                    ]"
                  >
                    {{ u.user_type === 'student' ? 'Student' : 'Graduate' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 max-w-[200px]">
                  <div class="font-medium text-white truncate">{{ u.institution }}</div>
                  <div class="text-[11px] text-zinc-400 truncate">{{ u.faculty_major }}</div>
                </td>
                <td class="py-3.5 px-4 font-mono text-zinc-300">
                  {{ u.graduation_year || '—' }}
                </td>
                <td class="py-3.5 px-4">
                  <span class="px-2 py-0.5 rounded bg-zinc-800 text-[11px] font-mono">
                    {{ osLabels[u.preferred_os] || u.preferred_os }}
                  </span>
                </td>
                <td class="py-3.5 px-4">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border',
                      u.status === 'invited_to_beta' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                    ]"
                  >
                    {{ u.status === 'invited_to_beta' ? 'Beta Access' : 'Pending' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-zinc-400 text-[11px] font-mono whitespace-nowrap">
                  {{ u.created_at ? u.created_at.split(' ')[0] : '—' }}
                </td>
                <td class="py-3.5 px-4 text-right">
                  <select
                    :value="u.status"
                    @change="updateStatus(u.id, ($event.target as HTMLSelectElement).value)"
                    class="px-2 py-1 rounded-lg bg-black/60 border border-white/10 text-[10px] font-mono text-zinc-300 focus:outline-none focus:border-rose-500 cursor-pointer"
                  >
                    <option value="pending">Mark Pending</option>
                    <option value="invited_to_beta">Invite to Beta</option>
                    <option value="approved">Mark Approved</option>
                  </select>
                </td>
              </tr>

              <tr v-if="!users.length && !loading">
                <td colspan="9" class="py-8 text-center text-zinc-500 text-xs italic">
                  No records match the current filter parameters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div v-if="pagination.total_pages > 1" class="p-4 border-t border-white/10 flex items-center justify-between text-xs text-zinc-400 font-mono">
          <div>
            Page {{ pagination.current_page }} of {{ pagination.total_pages }} ({{ pagination.total_records }} total)
          </div>
          <div class="flex gap-2">
            <button
              :disabled="pagination.current_page <= 1"
              @click="fetchUsers(pagination.current_page - 1)"
              class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 disabled:opacity-40 cursor-pointer"
            >
              Previous
            </button>
            <button
              :disabled="pagination.current_page >= pagination.total_pages"
              @click="fetchUsers(pagination.current_page + 1)"
              class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 disabled:opacity-40 cursor-pointer"
            >
              Next
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
