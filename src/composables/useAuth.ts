import { ref } from 'vue'

const currentUser = ref<any>(null)
const isAuthModalOpen = ref(false)
const authModalTab = ref<'signin' | 'signup'>('signup')
const isStatusModalOpen = ref(false)
const totalRegistered = ref(0)
const isInitialized = ref(false)

export function useAuth() {
  async function checkAuth() {
    try {
      const res = await fetch('/api/auth/me.php')
      const data = await res.json()
      if (data.success && data.data?.authenticated) {
        currentUser.value = data.data.user
        totalRegistered.value = data.data.total_registered || 0
      } else {
        currentUser.value = null
      }
    } catch (e) {
      const cached = localStorage.getItem('nex_user')
      if (cached) {
        try {
          currentUser.value = JSON.parse(cached)
        } catch (_) {}
      }
    } finally {
      isInitialized.value = true
    }
  }

  function openAuth(tab: 'signin' | 'signup' = 'signup') {
    authModalTab.value = tab
    isAuthModalOpen.value = true
  }

  function closeAuth() {
    isAuthModalOpen.value = false
  }

  function openStatusModal() {
    isStatusModalOpen.value = true
  }

  function closeStatusModal() {
    isStatusModalOpen.value = false
  }

  function setUser(user: any) {
    currentUser.value = user
  }

  async function logout() {
    try {
      await fetch('/api/auth/logout.php')
    } catch (_) {}
    localStorage.removeItem('nex_auth_token')
    localStorage.removeItem('nex_user')
    currentUser.value = null
    closeStatusModal()
  }

  return {
    currentUser,
    isAuthModalOpen,
    authModalTab,
    isStatusModalOpen,
    totalRegistered,
    isInitialized,
    checkAuth,
    openAuth,
    closeAuth,
    openStatusModal,
    closeStatusModal,
    setUser,
    logout
  }
}
