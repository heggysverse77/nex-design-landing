<script setup lang="ts">
import { ref, reactive, computed } from 'vue'

const props = defineProps<{
  isOpen: boolean
  initialTab?: 'signin' | 'signup'
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'success', user: any): void
}>()

const currentTab = ref<'signin' | 'signup'>(props.initialTab || 'signup')
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
  referral_source: 'Social Media'
})

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const years = []
  if (signupForm.user_type === 'student') {
    for (let y = currentYear; y <= currentYear + 6; y++) years.push(y)
  } else {
    for (let y = currentYear; y >= currentYear - 20; y--) years.push(y)
  }
  return years
})

async function handleSignIn() {
  errorMessage.value = ''
  successMessage.value = ''
  if (!signinForm.email || !signinForm.password) {
    errorMessage.value = 'Please enter both email and password.'
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
      errorMessage.value = data.error || 'Failed to sign in.'
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
        faculty_major: 'Design',
        graduation_year: 2026,
        preferred_os: 'windows',
        status: 'pending',
        waitlist_number: 142
      })
      const token = data?.data?.token || 'dev_token_' + Date.now()

      successMessage.value = 'Signed in successfully!'
      localStorage.setItem('nex_auth_token', token)
      localStorage.setItem('nex_user', JSON.stringify(userPayload))
      setTimeout(() => {
        emit('success', userPayload)
        emit('close')
      }, 700)
    }
  } catch (err: any) {
    if (!import.meta.env.DEV) {
      errorMessage.value = err instanceof Error ? err.message : 'Could not reach the account service.'
      return
    }
    const cached = localStorage.getItem('nex_user')
    const userPayload = cached ? JSON.parse(cached) : {
      id: 1,
      name: signinForm.email.split('@')[0] || 'User',
      email: signinForm.email,
      role: 'user',
      user_type: 'student',
      institution: 'University',
      faculty_major: 'Design',
      graduation_year: 2026,
      preferred_os: 'windows',
      status: 'pending',
      waitlist_number: 142
    }
    successMessage.value = 'Signed in successfully!'
    localStorage.setItem('nex_auth_token', 'dev_token_' + Date.now())
    localStorage.setItem('nex_user', JSON.stringify(userPayload))
    setTimeout(() => {
      emit('success', userPayload)
      emit('close')
    }, 700)
  } finally {
    loading.value = false
  }
}

async function handleSignUp() {
  errorMessage.value = ''
  successMessage.value = ''

  if (!signupForm.name || !signupForm.email || !signupForm.password || !confirmPassword.value) {
    errorMessage.value = 'Please fill out all required account fields.'
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
      ? 'Please provide your University and Faculty/Major.'
      : 'Please provide your Alma Mater and Specialization.'
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
      errorMessage.value = data.error || 'Registration failed.'
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

      successMessage.value = 'Early access reserved successfully!'
      localStorage.setItem('nex_auth_token', token)
      localStorage.setItem('nex_user', JSON.stringify(userPayload))
      setTimeout(() => {
        emit('success', userPayload)
        emit('close')
      }, 900)
    }
  } catch (err: any) {
    if (!import.meta.env.DEV) {
      errorMessage.value = err instanceof Error ? err.message : 'Could not reach the account service.'
      return
    }
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

    successMessage.value = 'Early access reserved successfully!'
    localStorage.setItem('nex_auth_token', token)
    localStorage.setItem('nex_user', JSON.stringify(userPayload))
    setTimeout(() => {
      emit('success', userPayload)
      emit('close')
    }, 900)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="emit('close')" />

    <!-- Modal Box -->
    <div class="relative w-full max-w-xl max-h-[92vh] overflow-y-auto rounded-2xl border border-white/10 bg-[#151418]/95 p-6 sm:p-8 shadow-2xl backdrop-blur-2xl scrollbar-thin text-white z-10">
      
      <!-- Close Button -->
      <button 
        @click="emit('close')"
        class="absolute top-5 right-5 p-2 text-zinc-400 hover:text-white rounded-lg hover:bg-white/5 transition"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Header & Tabs -->
      <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500/20 to-red-900/40 border border-rose-500/30 text-rose-400 mb-3 shadow-[0_0_20px_rgba(244,63,94,0.2)]">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold tracking-tight text-white">
          {{ currentTab === 'signup' ? 'Get Early Access to Nex Studio' : 'Welcome Back' }}
        </h2>
        <p class="text-xs text-zinc-400 mt-1 max-w-md mx-auto">
          {{ currentTab === 'signup' ? 'Reserve your account & priority queue for the upcoming Desktop App Beta release.' : 'Sign in to review your early access queue status & profile.' }}
        </p>

        <!-- Toggle Switch -->
        <div class="flex p-1 bg-black/40 border border-white/5 rounded-xl max-w-xs mx-auto mt-5">
          <button
            @click="currentTab = 'signup'; errorMessage = ''; successMessage = ''"
            :class="[
              'flex-1 py-1.5 text-xs font-semibold rounded-lg transition-all',
              currentTab === 'signup' ? 'bg-gradient-to-r from-rose-600 to-red-700 text-white shadow-lg' : 'text-zinc-400 hover:text-white'
            ]"
          >
            Early Access (Sign Up)
          </button>
          <button
            @click="currentTab = 'signin'; errorMessage = ''; successMessage = ''"
            :class="[
              'flex-1 py-1.5 text-xs font-semibold rounded-lg transition-all',
              currentTab === 'signin' ? 'bg-gradient-to-r from-rose-600 to-red-700 text-white shadow-lg' : 'text-zinc-400 hover:text-white'
            ]"
          >
            Sign In
          </button>
        </div>
      </div>

      <!-- Feedback Alerts -->
      <div v-if="errorMessage" class="mb-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>{{ errorMessage }}</span>
      </div>

      <div v-if="successMessage" class="mb-4 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>{{ successMessage }}</span>
      </div>

      <!-- SIGN IN FORM -->
      <form v-if="currentTab === 'signin'" @submit.prevent="handleSignIn" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-zinc-300 mb-1.5">Email Address</label>
          <input
            v-model="signinForm.email"
            type="email"
            required
            placeholder="you@example.com"
            class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-900/80 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500/60 focus:ring-1 focus:ring-rose-500/60 transition"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-zinc-300 mb-1.5">Password</label>
          <div class="relative">
            <input
              v-model="signinForm.password"
              :type="showSigninPassword ? 'text' : 'password'"
              required
              placeholder="••••••••"
              class="w-full px-3.5 py-2.5 pr-10 rounded-xl bg-zinc-900/80 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500/60 focus:ring-1 focus:ring-rose-500/60 transition"
            />
            <button 
              type="button" 
              @click="showSigninPassword = !showSigninPassword"
              class="absolute right-3 top-3 text-zinc-400 hover:text-zinc-200 transition"
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
          class="w-full mt-2 py-3 rounded-xl bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-500 hover:to-red-600 text-white font-semibold text-xs tracking-wide shadow-lg shadow-rose-900/30 disabled:opacity-50 transition cursor-pointer flex items-center justify-center gap-2"
        >
          <svg v-if="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
          <span>{{ loading ? 'Signing in...' : 'Sign In to Nex Account' }}</span>
        </button>
      </form>

      <!-- SIGN UP FORM -->
      <form v-else @submit.prevent="handleSignUp" class="space-y-4">
        
        <!-- User Type Switcher -->
        <div>
          <label class="block text-xs font-medium text-zinc-300 mb-2">I am currently a:</label>
          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="signupForm.user_type = 'student'"
              :class="[
                'p-3 rounded-xl border text-left flex items-start gap-3 transition-all',
                signupForm.user_type === 'student'
                  ? 'border-rose-500 bg-rose-500/10 text-white ring-1 ring-rose-500/50'
                  : 'border-white/10 bg-zinc-900/50 text-zinc-400 hover:border-white/20'
              ]"
            >
              <span class="text-xl">🎓</span>
              <div>
                <div class="text-xs font-semibold">University Student</div>
                <div class="text-[10px] text-zinc-400">Enrolled in college/university</div>
              </div>
            </button>

            <button
              type="button"
              @click="signupForm.user_type = 'graduate'"
              :class="[
                'p-3 rounded-xl border text-left flex items-start gap-3 transition-all',
                signupForm.user_type === 'graduate'
                  ? 'border-rose-500 bg-rose-500/10 text-white ring-1 ring-rose-500/50'
                  : 'border-white/10 bg-zinc-900/50 text-zinc-400 hover:border-white/20'
              ]"
            >
              <span class="text-xl">💼</span>
              <div>
                <div class="text-xs font-semibold">Graduated / Pro</div>
                <div class="text-[10px] text-zinc-400">Graduate, designer, engineer</div>
              </div>
            </button>
          </div>
        </div>

        <!-- Basic Account Details -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-medium text-zinc-300 mb-1">Full Name *</label>
            <input
              v-model="signupForm.name"
              type="text"
              required
              placeholder="Alex Smith"
              class="w-full px-3 py-2 rounded-xl bg-zinc-900/80 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500/60"
            />
          </div>

          <div>
            <label class="block text-[11px] font-medium text-zinc-300 mb-1">Email Address *</label>
            <input
              v-model="signupForm.email"
              type="email"
              required
              placeholder="alex@university.edu"
              class="w-full px-3 py-2 rounded-xl bg-zinc-900/80 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500/60"
            />
          </div>
        </div>

        <!-- Password and Confirm Password -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-medium text-zinc-300 mb-1">Password *</label>
            <div class="relative">
              <input
                v-model="signupForm.password"
                :type="showSignupPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="w-full px-3 py-2 pr-9 rounded-xl bg-zinc-900/80 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500/60 transition"
              />
              <button 
                type="button" 
                @click="showSignupPassword = !showSignupPassword"
                class="absolute right-2.5 top-2.5 text-zinc-400 hover:text-zinc-200 transition"
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
            <label class="block text-[11px] font-medium text-zinc-300 mb-1">Confirm Password *</label>
            <div class="relative">
              <input
                v-model="confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="w-full px-3 py-2 pr-9 rounded-xl bg-zinc-900/80 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500/60 transition"
              />
              <button 
                type="button" 
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-2.5 top-2.5 text-zinc-400 hover:text-zinc-200 transition"
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

        <!-- Live Password Requirements Checklist -->
        <div class="p-3 rounded-xl bg-zinc-900/60 border border-white/5 space-y-1.5 text-[11px]">
          <div class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider">Password Requirements</div>
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

        <!-- Dynamic Fields for Students -->
        <div v-if="signupForm.user_type === 'student'" class="p-3.5 rounded-xl bg-white/[0.02] border border-white/10 space-y-3">
          <div class="text-[11px] font-semibold text-rose-400 flex items-center gap-1.5">
            <span>🎓 Academic Credentials</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] text-zinc-300 mb-1">University / College *</label>
              <input
                v-model="signupForm.institution"
                type="text"
                required
                placeholder="e.g. Cairo University / MIT"
                class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500"
              />
            </div>

            <div>
              <label class="block text-[11px] text-zinc-300 mb-1">Faculty / Major *</label>
              <input
                v-model="signupForm.faculty_major"
                type="text"
                required
                placeholder="e.g. Applied Arts, CS, Architecture"
                class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] text-zinc-300 mb-1">Expected Graduation Year</label>
              <select
                v-model="signupForm.graduation_year"
                class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white text-xs focus:outline-none focus:border-rose-500"
              >
                <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] text-zinc-300 mb-1">Student ID (Optional)</label>
              <input
                v-model="signupForm.student_id_number"
                type="text"
                placeholder="e.g. 2024-ST-881"
                class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500"
              />
            </div>
          </div>
        </div>

        <!-- Dynamic Fields for Graduates / Professionals -->
        <div v-else class="p-3.5 rounded-xl bg-white/[0.02] border border-white/10 space-y-3">
          <div class="text-[11px] font-semibold text-rose-400 flex items-center gap-1.5">
            <span>💼 Career & Education Background</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] text-zinc-300 mb-1">Alma Mater / University *</label>
              <input
                v-model="signupForm.institution"
                type="text"
                required
                placeholder="University or College graduated from"
                class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500"
              />
            </div>

            <div>
              <label class="block text-[11px] text-zinc-300 mb-1">Field / Degree *</label>
              <input
                v-model="signupForm.faculty_major"
                type="text"
                required
                placeholder="e.g. Graphic Design / Software Eng."
                class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-[11px] text-zinc-300 mb-1">Graduation Year</label>
              <select
                v-model="signupForm.graduation_year"
                class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white text-xs focus:outline-none focus:border-rose-500"
              >
                <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>

            <div>
              <label class="block text-[11px] text-zinc-300 mb-1">Current Role / Studio (Optional)</label>
              <input
                v-model="signupForm.current_role"
                type="text"
                placeholder="e.g. Lead UI Designer / Freelancer"
                class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-[11px] text-zinc-300 mb-1">Portfolio / GitHub / Behance Link (Optional)</label>
            <input
              v-model="signupForm.portfolio_url"
              type="url"
              placeholder="https://behance.net/username or https://github.com/..."
              class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-white/10 text-white placeholder-zinc-500 text-xs focus:outline-none focus:border-rose-500"
            />
          </div>
        </div>

        <!-- Target Desktop App OS -->
        <div>
          <label class="block text-[11px] font-medium text-zinc-300 mb-1.5">Preferred Desktop OS for Beta App *</label>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <button
              v-for="os in [
                { id: 'windows', label: 'Windows', icon: '🪟' },
                { id: 'mac_arm', label: 'Mac (M-Series)', icon: '🍏' },
                { id: 'mac_intel', label: 'Mac (Intel)', icon: '💻' },
                { id: 'linux', label: 'Linux', icon: '🐧' }
              ]"
              :key="os.id"
              type="button"
              @click="signupForm.preferred_os = os.id"
              :class="[
                'p-2 rounded-xl border text-center transition-all',
                signupForm.preferred_os === os.id
                  ? 'border-rose-500 bg-rose-500/20 text-white ring-1 ring-rose-500'
                  : 'border-white/10 bg-zinc-900/60 text-zinc-400 hover:text-white'
              ]"
            >
              <div class="text-sm mb-0.5">{{ os.icon }}</div>
              <div class="text-[10px] font-medium truncate">{{ os.label }}</div>
            </button>
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full mt-3 py-3 rounded-xl bg-gradient-to-r from-rose-600 via-red-600 to-rose-700 hover:from-rose-500 hover:to-red-600 text-white font-semibold text-xs tracking-wide shadow-lg shadow-rose-900/40 disabled:opacity-50 transition cursor-pointer flex items-center justify-center gap-2"
        >
          <svg v-if="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
          <span>{{ loading ? 'Securing your spot...' : '🚀 Reserve Early Access & Create Account' }}</span>
        </button>
      </form>
    </div>
  </div>
</template>
