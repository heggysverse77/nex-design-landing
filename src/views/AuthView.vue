<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import NexLogo from '@/components/landing/NexLogo.vue'

const router = useRouter()
const route = useRoute()
const { currentUser, checkAuth, setUser, logout } = useAuth()

const currentTab = ref<'signup' | 'signin'>('signup')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const showSigninPassword = ref(false)
const showSignupPassword = ref(false)
const showConfirmPassword = ref(false)
const confirmPassword = ref('')

// Password security requirement checker
const passwordRequirements = computed(() => {
  const pwd = signupForm.password
  return {
    minLength: pwd.length >= 8,
    hasUppercase: /[A-Z]/.test(pwd),
    hasSymbol: /[^A-Za-z0-9]/.test(pwd),
    matchesConfirm: pwd.length > 0 && pwd === confirmPassword.value
  }
})

// Sign in form
const signinForm = reactive({
  email: '',
  password: ''
})

// Sign up form
const signupForm = reactive({
  name: '',
  email: '',
  password: '',
  user_type: 'student', // 'student' | 'graduate'
  institution: '',
  faculty_major: '',
  graduation_year: 2026,
  student_id_number: '',
  current_role: '',
  portfolio_url: '',
  preferred_os: 'windows',
  primary_use_case: 'UI/UX & Prototyping',
  referral_source: 'Direct'
})

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  if (signupForm.user_type === 'student') {
    for (let y = currentYear; y <= currentYear + 6; y++) years.push(y)
  } else {
    for (let y = currentYear; y >= currentYear - 25; y--) years.push(y)
  }
  return years
})

const osOptions = [
  { id: 'windows', label: 'Windows', sub: 'x64' },
  { id: 'mac_arm', label: 'macOS', sub: 'Apple Silicon' },
  { id: 'mac_intel', label: 'macOS', sub: 'Intel' },
  { id: 'linux', label: 'Linux', sub: 'x64' }
]

const osLabels: Record<string, string> = {
  windows: 'Windows (x64)',
  mac_arm: 'macOS (Apple Silicon)',
  mac_intel: 'macOS (Intel)',
  linux: 'Linux (x64)'
}

onMounted(async () => {
  await checkAuth()
  currentTab.value = 'signup'
})


async function handleSignIn() {
  errorMessage.value = ''
  successMessage.value = ''
  if (!signinForm.email || !signinForm.password) {
    errorMessage.value = 'Please enter both your email address and password.'
    return
  }

  loading.value = true
  try {
    const res = await fetch('/api/auth/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(signinForm)
    })

    let data: any = null
    const contentType = res.headers.get('content-type')
    if (contentType && contentType.includes('application/json')) {
      data = await res.json()
    }

    if (data && !data.success) {
      errorMessage.value = data.error || 'Invalid credentials.'
    } else {
      if (!data?.data?.user || !data?.data?.token) throw new Error('Invalid authentication response')
      const cached = localStorage.getItem('nex_user')
      const userPayload = data?.data?.user || (cached ? JSON.parse(cached) : {
        id: 1,
        name: signinForm.email.split('@')[0] || 'User',
        email: signinForm.email,
        role: 'user',
        user_type: 'student',
        institution: 'University',
        faculty_major: 'Design & Engineering',
        graduation_year: 2026,
        preferred_os: 'windows',
        status: 'pending',
        waitlist_number: 142
      })
      const token = data?.data?.token || 'dev_token_' + Date.now()

      successMessage.value = 'Authenticated successfully.'
      localStorage.setItem('nex_auth_token', token)
      localStorage.setItem('nex_user', JSON.stringify(userPayload))
      setUser(userPayload)
    }
  } catch (err) {
    if (!import.meta.env.DEV) {
      errorMessage.value = err instanceof Error ? err.message : 'Could not reach the account service.'
      return
    }
    // Fallback for local Vite development when PHP backend is offline
    const cached = localStorage.getItem('nex_user')
    const userPayload = cached ? JSON.parse(cached) : {
      id: 1,
      name: signinForm.email.split('@')[0] || 'User',
      email: signinForm.email,
      role: 'user',
      user_type: 'student',
      institution: 'University',
      faculty_major: 'Design & Engineering',
      graduation_year: 2026,
      preferred_os: 'windows',
      status: 'pending',
      waitlist_number: 142
    }
    successMessage.value = 'Authenticated successfully.'
    localStorage.setItem('nex_auth_token', 'dev_token_' + Date.now())
    localStorage.setItem('nex_user', JSON.stringify(userPayload))
    setUser(userPayload)
  } finally {
    loading.value = false
  }
}

async function handleSignUp() {
  errorMessage.value = ''
  successMessage.value = ''
  
  if (!signupForm.name || !signupForm.email || !signupForm.password || !confirmPassword.value) {
    errorMessage.value = 'Please provide all required account credentials.'
    return
  }

  if (signupForm.password.length < 8) {
    errorMessage.value = 'Password must be at least 8 characters long.'
    return
  }

  if (!/[A-Z]/.test(signupForm.password)) {
    errorMessage.value = 'Password must contain at least one uppercase letter (A-Z).'
    return
  }

  if (!/[^A-Za-z0-9]/.test(signupForm.password)) {
    errorMessage.value = 'Password must contain at least one special symbol (e.g. !@#$%^&*).'
    return
  }

  if (signupForm.password !== confirmPassword.value) {
    errorMessage.value = 'Password and Confirm Password do not match.'
    return
  }

  if (!signupForm.institution || !signupForm.faculty_major) {
    errorMessage.value = signupForm.user_type === 'student'
      ? 'Please specify your University and Faculty/Major.'
      : 'Please specify your Alma Mater and Specialization.'
    return
  }

  loading.value = true
  try {
    const res = await fetch('/api/auth/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(signupForm)
    })

    let data: any = null
    const contentType = res.headers.get('content-type')
    if (contentType && contentType.includes('application/json')) {
      data = await res.json()
    }

    if (data && !data.success) {
      errorMessage.value = data.error || 'Registration could not be completed.'
    } else {
      if (!data?.data?.user || !data?.data?.token) throw new Error('Invalid registration response')
      const userPayload = data?.data?.user || {
        id: Date.now(),
        name: signupForm.name,
        email: signupForm.email,
        role: 'user',
        user_type: signupForm.user_type,
        institution: signupForm.institution,
        faculty_major: signupForm.faculty_major,
        graduation_year: signupForm.graduation_year,
        student_id_number: signupForm.student_id_number,
        current_role: signupForm.current_role,
        portfolio_url: signupForm.portfolio_url,
        preferred_os: signupForm.preferred_os,
        status: 'pending',
        waitlist_number: Math.floor(Math.random() * 50) + 120
      }
      const token = data?.data?.token || 'dev_token_' + Date.now()

      successMessage.value = 'Account created! Your early access position is reserved.'
      localStorage.setItem('nex_auth_token', token)
      localStorage.setItem('nex_user', JSON.stringify(userPayload))
      setUser(userPayload)
    }
  } catch (err) {
    if (!import.meta.env.DEV) {
      errorMessage.value = err instanceof Error ? err.message : 'Could not reach the account service.'
      return
    }
    // Fallback for local Vite development when PHP backend is offline
    const userPayload = {
      id: Date.now(),
      name: signupForm.name,
      email: signupForm.email,
      role: 'user',
      user_type: signupForm.user_type,
      institution: signupForm.institution,
      faculty_major: signupForm.faculty_major,
      graduation_year: signupForm.graduation_year,
      student_id_number: signupForm.student_id_number,
      current_role: signupForm.current_role,
      portfolio_url: signupForm.portfolio_url,
      preferred_os: signupForm.preferred_os,
      status: 'pending',
      waitlist_number: Math.floor(Math.random() * 50) + 120
    }
    const token = 'dev_token_' + Date.now()

    successMessage.value = 'Account created! Your early access position is reserved.'
    localStorage.setItem('nex_auth_token', token)
    localStorage.setItem('nex_user', JSON.stringify(userPayload))
    setUser(userPayload)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#070709] text-[#eae8e4] font-sans relative selection:bg-rose-600 selection:text-white flex flex-col justify-between">
    
    <!-- Ambient Background Gradients -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
      <div class="absolute -top-[20%] left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-gradient-to-b from-rose-600/10 via-red-900/5 to-transparent blur-[140px]" />
      <div class="absolute bottom-0 inset-x-0 h-48 bg-gradient-to-t from-black via-black/80 to-transparent" />
      <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,0.03)_1px,transparent_1px)] [background-size:28px_28px]" />
    </div>

    <!-- Top Navigation Bar -->
    <header class="relative z-20 max-w-7xl mx-auto px-6 py-6 w-full flex items-center justify-between">
      <router-link to="/" class="flex items-center gap-3 group">
        <NexLogo :size="26" variant="white" :show-text="false" />
        <span class="font-bold text-xs tracking-[0.25em] text-[#f5f4f0] font-sans">NEX DESIGN</span>
      </router-link>

      <router-link
        to="/"
        class="text-[11px] font-mono tracking-widest text-[#a1a1aa] hover:text-white transition flex items-center gap-1.5"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        <span>BACK TO STUDIO</span>
      </router-link>
    </header>

    <!-- Main Content Container -->
    <main class="relative z-10 max-w-xl w-full mx-auto px-4 py-8">
      
      <!-- LOGGED IN STATE: USER STATUS CARD -->
      <div v-if="currentUser" class="rounded-2xl border border-white/10 bg-[#121115]/90 p-8 shadow-2xl backdrop-blur-2xl text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-zinc-800 to-zinc-900 border border-white/10 text-xl font-bold text-white mb-4 shadow-lg">
          {{ currentUser.name.charAt(0).toUpperCase() }}
        </div>
        
        <h1 class="text-2xl font-bold text-white tracking-tight">{{ currentUser.name }}</h1>
        <p class="text-xs text-zinc-400 font-mono mt-1">{{ currentUser.email }}</p>

        <!-- Queue Status Panel -->
        <div class="mt-6 p-6 rounded-xl bg-black/50 border border-white/10 text-center">
          <div class="text-[10px] font-mono font-bold uppercase tracking-widest text-rose-400">Early Access Queue Spot</div>
          <div class="text-4xl font-extrabold text-white font-mono my-2 flex items-center justify-center gap-1">
            <span class="text-rose-500">#</span>{{ currentUser.waitlist_number || '1' }}
          </div>
          <div class="text-xs text-zinc-400">
            Status:
            <span class="font-semibold text-emerald-400 uppercase tracking-wide">
              {{ currentUser.status === 'invited_to_beta' ? 'Beta Access Active' : 'Reserved for Wave 1' }}
            </span>
          </div>
        </div>

        <!-- User Credentials Summary -->
        <div class="mt-6 space-y-2.5 text-xs text-left">
          <div class="flex justify-between py-2 border-b border-white/5">
            <span class="text-zinc-400">Account Type</span>
            <span class="font-medium text-white">{{ currentUser.user_type === 'student' ? 'University Student' : 'Graduate / Professional' }}</span>
          </div>

          <div class="flex justify-between py-2 border-b border-white/5">
            <span class="text-zinc-400">{{ currentUser.user_type === 'student' ? 'University' : 'Alma Mater' }}</span>
            <span class="font-medium text-white truncate max-w-[240px]">{{ currentUser.institution }}</span>
          </div>

          <div class="flex justify-between py-2 border-b border-white/5">
            <span class="text-zinc-400">Faculty / Field</span>
            <span class="font-medium text-white truncate max-w-[240px]">{{ currentUser.faculty_major }}</span>
          </div>

          <div v-if="currentUser.graduation_year" class="flex justify-between py-2 border-b border-white/5">
            <span class="text-zinc-400">{{ currentUser.user_type === 'student' ? 'Expected Graduation' : 'Graduated' }}</span>
            <span class="font-medium text-white font-mono">{{ currentUser.graduation_year }}</span>
          </div>

          <div class="flex justify-between py-2 border-b border-white/5">
            <span class="text-zinc-400">Desktop Platform</span>
            <span class="font-medium text-rose-400 font-mono">{{ osLabels[currentUser.preferred_os] || currentUser.preferred_os }}</span>
          </div>
        </div>

        <!-- Buttons -->
        <div class="mt-8 flex items-center gap-3">
          <router-link
            to="/"
            class="flex-1 py-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-mono text-xs font-bold tracking-wider text-center transition shadow-lg shadow-rose-950/40"
          >
            EXPLORE STUDIO
          </router-link>
          
          <button
            @click="logout"
            class="px-5 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-zinc-300 hover:text-white font-mono text-xs border border-white/10 transition"
          >
            SIGN OUT
          </button>
        </div>
      </div>

      <!-- NOT LOGGED IN: SIGN UP / SIGN IN FORM -->
      <div v-else class="rounded-2xl border border-white/10 bg-[#121115]/90 p-6 sm:p-8 shadow-2xl backdrop-blur-2xl">
        
        <!-- Header -->
        <div class="text-center mb-6">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-zinc-900 border border-white/10 text-rose-400 mb-3 shadow-inner">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h1 class="text-2xl font-extrabold tracking-tight text-white font-sans">
            {{ currentTab === 'signup' ? 'Nex Studio Desktop Access' : 'Sign In to Account' }}
          </h1>
          <p class="text-xs text-zinc-400 mt-1 max-w-sm mx-auto leading-relaxed">
            {{ currentTab === 'signup' ? 'Reserve your account and priority queue for the upcoming standalone desktop application.' : 'Sign in to access your early-access account and priority rank.' }}
          </p>

          <!-- Tab Switcher -->
          <div class="flex p-1 bg-black/60 border border-white/5 rounded-xl max-w-xs mx-auto mt-6">
            <button
              @click="currentTab = 'signup'; errorMessage = ''; successMessage = ''"
              :class="[
                'flex-1 py-1.5 text-xs font-mono font-bold tracking-wider rounded-lg transition-all',
                currentTab === 'signup' ? 'bg-gradient-to-r from-rose-600 to-red-700 text-white shadow-md' : 'text-zinc-400 hover:text-white'
              ]"
            >
              EARLY ACCESS
            </button>
            <button
              @click="currentTab = 'signin'; errorMessage = ''; successMessage = ''"
              :class="[
                'flex-1 py-1.5 text-xs font-mono font-bold tracking-wider rounded-lg transition-all',
                currentTab === 'signin' ? 'bg-gradient-to-r from-rose-600 to-red-700 text-white shadow-md' : 'text-zinc-400 hover:text-white'
              ]"
            >
              SIGN IN
            </button>
          </div>
        </div>

        <!-- Alert Banners -->
        <div v-if="errorMessage" class="mb-5 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2.5">
          <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ errorMessage }}</span>
        </div>

        <div v-if="successMessage" class="mb-5 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2.5">
          <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>{{ successMessage }}</span>
        </div>

        <!-- SIGN IN FORM -->
        <form v-if="currentTab === 'signin'" @submit.prevent="handleSignIn" class="space-y-4">
          <div>
            <label class="block text-[11px] font-mono tracking-wider uppercase text-zinc-400 mb-1.5">Email Address</label>
            <input
              v-model="signinForm.email"
              type="email"
              required
              placeholder="name@institution.edu"
              class="w-full px-3.5 py-2.5 rounded-xl bg-black/40 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500/60 focus:ring-1 focus:ring-rose-500/60 transition"
            />
          </div>

          <div>
            <label class="block text-[11px] font-mono tracking-wider uppercase text-zinc-400 mb-1.5">Password</label>
            <div class="relative">
              <input
                v-model="signinForm.password"
                :type="showSigninPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-black/40 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500/60 focus:ring-1 focus:ring-rose-500/60 transition"
              />
              <button
                type="button"
                @click="showSigninPassword = !showSigninPassword"
                class="absolute right-3 top-2.5 text-zinc-500 hover:text-zinc-300"
                title="Toggle Password Visibility"
              >
                <svg v-if="!showSigninPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full mt-2 py-3 rounded-xl bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-500 hover:to-red-600 text-white font-mono font-bold text-xs tracking-wider shadow-lg shadow-rose-950/40 disabled:opacity-50 transition cursor-pointer flex items-center justify-center gap-2"
          >
            <svg v-if="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            <span>{{ loading ? 'AUTHENTICATING...' : 'SIGN IN' }}</span>
          </button>
        </form>

        <!-- SIGN UP / EARLY ACCESS FORM -->
        <form v-else @submit.prevent="handleSignUp" class="space-y-4">
          
          <!-- Category Selector -->
          <div>
            <label class="block text-[11px] font-mono tracking-wider uppercase text-zinc-400 mb-2">Category</label>
            <div class="grid grid-cols-2 gap-3">
              <button
                type="button"
                @click="signupForm.user_type = 'student'"
                :class="[
                  'p-3.5 rounded-xl border text-left flex items-start gap-3 transition-all cursor-pointer',
                  signupForm.user_type === 'student'
                    ? 'border-rose-500/80 bg-rose-500/10 text-white ring-1 ring-rose-500/30'
                    : 'border-white/10 bg-black/40 text-zinc-400 hover:border-white/20'
                ]"
              >
                <div class="p-2 rounded-lg bg-white/5 text-rose-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                  </svg>
                </div>
                <div>
                  <div class="text-xs font-bold text-white">University Student</div>
                  <div class="text-[10px] text-zinc-400 mt-0.5 leading-tight">Undergraduate or postgraduate</div>
                </div>
              </button>

              <button
                type="button"
                @click="signupForm.user_type = 'graduate'"
                :class="[
                  'p-3.5 rounded-xl border text-left flex items-start gap-3 transition-all cursor-pointer',
                  signupForm.user_type === 'graduate'
                    ? 'border-rose-500/80 bg-rose-500/10 text-white ring-1 ring-rose-500/30'
                    : 'border-white/10 bg-black/40 text-zinc-400 hover:border-white/20'
                ]"
              >
                <div class="p-2 rounded-lg bg-white/5 text-rose-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <div class="text-xs font-bold text-white">Graduate / Pro</div>
                  <div class="text-[10px] text-zinc-400 mt-0.5 leading-tight">Designer, studio, engineer</div>
                </div>
              </button>
            </div>
          </div>

          <!-- Basic Account Fields -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-mono tracking-wider uppercase text-zinc-400 mb-1">Full Name *</label>
              <input
                v-model="signupForm.name"
                type="text"
                required
                placeholder="Alex Smith"
                class="w-full px-3.5 py-2 rounded-xl bg-black/40 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500/60"
              />
            </div>

            <div>
              <label class="block text-[11px] font-mono tracking-wider uppercase text-zinc-400 mb-1">Email Address *</label>
              <input
                v-model="signupForm.email"
                type="email"
                required
                placeholder="alex@university.edu"
                class="w-full px-3.5 py-2 rounded-xl bg-black/40 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500/60"
              />
            </div>
          </div>

          <!-- Password & Confirm Password with Eye Toggles -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] font-mono tracking-wider uppercase text-zinc-400 mb-1">Password *</label>
              <div class="relative">
                <input
                  v-model="signupForm.password"
                  :type="showSignupPassword ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                  class="w-full px-3.5 py-2 pr-9 rounded-xl bg-black/40 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500/60 transition"
                />
                <button
                  type="button"
                  @click="showSignupPassword = !showSignupPassword"
                  class="absolute right-2.5 top-2.5 text-zinc-500 hover:text-zinc-300"
                  title="Toggle Password Visibility"
                >
                  <svg v-if="!showSignupPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                  </svg>
                </button>
              </div>
            </div>

            <div>
              <label class="block text-[11px] font-mono tracking-wider uppercase text-zinc-400 mb-1">Confirm Password *</label>
              <div class="relative">
                <input
                  v-model="confirmPassword"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                  class="w-full px-3.5 py-2 pr-9 rounded-xl bg-black/40 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500/60 transition"
                />
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute right-2.5 top-2.5 text-zinc-500 hover:text-zinc-300"
                  title="Toggle Confirm Password Visibility"
                >
                  <svg v-if="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Password Requirements Checklist -->
          <div class="p-3 rounded-xl bg-black/40 border border-white/5 space-y-1.5 text-[11px]">
            <div class="text-[10px] font-mono font-semibold text-zinc-400 uppercase tracking-wider">Password Requirements</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
              <div :class="['flex items-center gap-1.5 font-medium transition-colors', passwordRequirements.minLength ? 'text-emerald-400' : 'text-zinc-500']">
                <span>{{ passwordRequirements.minLength ? '✓' : '○' }}</span> At least 8 characters
              </div>
              <div :class="['flex items-center gap-1.5 font-medium transition-colors', passwordRequirements.hasUppercase ? 'text-emerald-400' : 'text-zinc-500']">
                <span>{{ passwordRequirements.hasUppercase ? '✓' : '○' }}</span> 1+ Uppercase letter (A-Z)
              </div>
              <div :class="['flex items-center gap-1.5 font-medium transition-colors', passwordRequirements.hasSymbol ? 'text-emerald-400' : 'text-zinc-500']">
                <span>{{ passwordRequirements.hasSymbol ? '✓' : '○' }}</span> 1+ Special symbol (!@#$)
              </div>
              <div :class="['flex items-center gap-1.5 font-medium transition-colors', passwordRequirements.matchesConfirm ? 'text-emerald-400' : 'text-zinc-500']">
                <span>{{ passwordRequirements.matchesConfirm ? '✓' : '○' }}</span> Passwords match
              </div>
            </div>
          </div>

          <!-- Student Section -->
          <div v-if="signupForm.user_type === 'student'" class="p-4 rounded-xl bg-white/[0.02] border border-white/10 space-y-3">
            <div class="text-[11px] font-mono font-bold tracking-wider uppercase text-rose-400">
              Academic Credentials
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] text-zinc-400 mb-1">University / College *</label>
                <input
                  v-model="signupForm.institution"
                  type="text"
                  required
                  placeholder="e.g. Cairo University, MIT"
                  class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500"
                />
              </div>

              <div>
                <label class="block text-[11px] text-zinc-400 mb-1">Faculty / Major *</label>
                <input
                  v-model="signupForm.faculty_major"
                  type="text"
                  required
                  placeholder="e.g. Applied Arts, UI/UX, CS"
                  class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] text-zinc-400 mb-1">Expected Graduation</label>
                <select
                  v-model="signupForm.graduation_year"
                  class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white text-xs focus:outline-none focus:border-rose-500"
                >
                  <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] text-zinc-400 mb-1">Student ID (Optional)</label>
                <input
                  v-model="signupForm.student_id_number"
                  type="text"
                  placeholder="e.g. 2024-ST-881"
                  class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500"
                />
              </div>
            </div>
          </div>

          <!-- Graduate Section -->
          <div v-else class="p-4 rounded-xl bg-white/[0.02] border border-white/10 space-y-3">
            <div class="text-[11px] font-mono font-bold tracking-wider uppercase text-rose-400">
              Professional & Education Profile
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] text-zinc-400 mb-1">Alma Mater / University *</label>
                <input
                  v-model="signupForm.institution"
                  type="text"
                  required
                  placeholder="University graduated from"
                  class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500"
                />
              </div>

              <div>
                <label class="block text-[11px] text-zinc-400 mb-1">Specialization / Degree *</label>
                <input
                  v-model="signupForm.faculty_major"
                  type="text"
                  required
                  placeholder="e.g. Graphic Design, Architecture"
                  class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-[11px] text-zinc-400 mb-1">Graduation Year</label>
                <select
                  v-model="signupForm.graduation_year"
                  class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white text-xs focus:outline-none focus:border-rose-500"
                >
                  <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>

              <div>
                <label class="block text-[11px] text-zinc-400 mb-1">Current Role / Studio (Optional)</label>
                <input
                  v-model="signupForm.current_role"
                  type="text"
                  placeholder="e.g. Senior Product Designer"
                  class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500"
                />
              </div>
            </div>

            <div>
              <label class="block text-[11px] text-zinc-400 mb-1">Portfolio or GitHub Link (Optional)</label>
              <input
                v-model="signupForm.portfolio_url"
                type="url"
                placeholder="https://behance.net/username or https://..."
                class="w-full px-3 py-2 rounded-lg bg-black/50 border border-white/10 text-white placeholder-zinc-600 text-xs focus:outline-none focus:border-rose-500"
              />
            </div>
          </div>

          <!-- Target Operating System -->
          <div>
            <label class="block text-[11px] font-mono tracking-wider uppercase text-zinc-400 mb-1.5">Target Desktop OS *</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
              <button
                v-for="os in osOptions"
                :key="os.id"
                type="button"
                @click="signupForm.preferred_os = os.id"
                :class="[
                  'p-2.5 rounded-xl border text-center transition-all cursor-pointer',
                  signupForm.preferred_os === os.id
                    ? 'border-rose-500/80 bg-rose-500/15 text-white ring-1 ring-rose-500/40'
                    : 'border-white/10 bg-black/40 text-zinc-400 hover:text-white'
                ]"
              >
                <div class="text-xs font-bold font-mono text-white">{{ os.label }}</div>
                <div class="text-[10px] text-zinc-500 font-mono">{{ os.sub }}</div>
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="w-full mt-3 py-3.5 rounded-xl bg-gradient-to-r from-rose-600 via-red-600 to-rose-700 hover:from-rose-500 hover:to-red-600 text-white font-mono font-bold text-xs tracking-widest shadow-lg shadow-rose-950/40 disabled:opacity-50 transition cursor-pointer flex items-center justify-center gap-2"
          >
            <svg v-if="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            <span>{{ loading ? 'SECURING POSITION...' : 'RESERVE EARLY ACCESS' }}</span>
          </button>
        </form>

      </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-20 max-w-7xl mx-auto px-6 py-6 w-full text-center text-zinc-600 font-mono text-[11px] tracking-wider">
      &copy; 2026 NEX DESIGN STUDIO &bull; STANDALONE DESKTOP PRE-RELEASE
    </footer>

  </div>
</template>
