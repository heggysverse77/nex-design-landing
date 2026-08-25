<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import AnimatedLoader from '@/components/landing/AnimatedLoader.vue'
import MarbleBackground from '@/components/landing/MarbleBackground.vue'
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingHero from '@/components/landing/LandingHero.vue'
import BreakTheWebsite from '@/components/landing/BreakTheWebsite.vue'
import ProductShowcase from '@/components/landing/ProductShowcase.vue'
import DesignGuide from '@/components/landing/DesignGuide.vue'
import Prototyping from '@/components/landing/Prototyping.vue'
import FreeAIHelper from '@/components/landing/FreeAIHelper.vue'
import Typography from '@/components/landing/Typography.vue'
import ComponentsSystem from '@/components/landing/ComponentsSystem.vue'
import Collaboration from '@/components/landing/Collaboration.vue'
import LandingFeatures from '@/components/landing/LandingFeatures.vue'
import WhyNex from '@/components/landing/WhyNex.vue'
import Pricing from '@/components/landing/Pricing.vue'
import FinalCTA from '@/components/landing/FinalCTA.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'
import AuthModal from '@/components/auth/AuthModal.vue'
import UserStatusModal from '@/components/auth/UserStatusModal.vue'

const isLoaded = ref(false)
const { 
  currentUser, 
  isAuthModalOpen, 
  authModalTab, 
  isStatusModalOpen, 
  totalRegistered,
  checkAuth, 
  closeAuth, 
  closeStatusModal,
  setUser,
  logout 
} = useAuth()

onMounted(() => {
  checkAuth()
  document.documentElement.classList.add('scrollable-page')
  document.body.classList.add('scrollable-page')
  const app = document.getElementById('app')
  if (app) app.classList.add('scrollable-page')
})

onUnmounted(() => {
  document.documentElement.classList.remove('scrollable-page')
  document.body.classList.remove('scrollable-page')
  const app = document.getElementById('app')
  if (app) app.classList.remove('scrollable-page')
})

function onLoaderComplete() {
  isLoaded.value = true
}
</script>

<template>
  <div class="relative min-h-screen bg-[#000000] text-[#eae8e4] overflow-x-hidden font-sans">
    <!-- Animated Intro Heartbeat Loader Overlay -->
    <AnimatedLoader @complete="onLoaderComplete" />

    <!-- Organic Marble Wave Canvas Backdrop -->
    <MarbleBackground />

    <!-- Landing View Main Content -->
    <div class="relative z-10 transition-opacity duration-700" :class="{ 'opacity-100': isLoaded, 'opacity-0': !isLoaded }">
      <LandingNavbar />
      <LandingHero />
      <BreakTheWebsite />
      <ProductShowcase />
      <DesignGuide />
      <Prototyping />
      <FreeAIHelper />
      <Typography />
      <ComponentsSystem />
      <Collaboration />
      <LandingFeatures />
      <WhyNex />
      <Pricing />
      <FinalCTA />
      <LandingFooter />
    </div>

    <!-- Auth & Early Access Modals -->
    <AuthModal
      :is-open="isAuthModalOpen"
      :initial-tab="authModalTab"
      @close="closeAuth"
      @success="setUser"
    />

    <UserStatusModal
      :is-open="isStatusModalOpen"
      :user="currentUser"
      :total-registered="totalRegistered"
      @close="closeStatusModal"
      @logout="logout"
    />
  </div>
</template>


