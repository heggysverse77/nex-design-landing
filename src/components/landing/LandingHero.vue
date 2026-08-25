<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useHeroCustomizer } from '@/composables/useHeroCustomizer'

const router = useRouter()
const { heroState } = useHeroCustomizer()
const heroRef = ref<HTMLElement | null>(null)
const lavaCanvasRef = ref<HTMLCanvasElement | null>(null)

// Headline Spotlight Hover logic
const isHovered = ref(false)
const headlineMouseX = ref(0)
const headlineMouseY = ref(0)

function handleHeadlineMouseMove(e: MouseEvent) {
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  headlineMouseX.value = e.clientX - rect.left
  headlineMouseY.value = e.clientY - rect.top
}

// Background Parallax Mouse tracking
const targetMouseX = ref(0)
const targetMouseY = ref(0)
const smoothedMouseX = ref(0)
const smoothedMouseY = ref(0)
let animationFrameId: number | null = null
let lavaAnimationId: number | null = null

function handleHeroMouseMove(e: MouseEvent) {
  if (!heroRef.value) return
  const rect = heroRef.value.getBoundingClientRect()
  targetMouseX.value = (e.clientX - rect.left) / rect.width - 0.5
  targetMouseY.value = (e.clientY - rect.top) / rect.height - 0.5
}

function updateParallax() {
  smoothedMouseX.value += (targetMouseX.value - smoothedMouseX.value) * 0.05
  smoothedMouseY.value += (targetMouseY.value - smoothedMouseY.value) * 0.05
  animationFrameId = requestAnimationFrame(updateParallax)
}

// Molten Lava Engine Data & Types
interface LavaBlob {
  x: number
  y: number
  radius: number
  baseRadius: number
  vy: number
  vx: number
  color: string
  pulseOffset: number
  pulseSpeed: number
}

interface LavaEmber {
  x: number
  y: number
  size: number
  vy: number
  vx: number
  opacity: number
  color: string
}

// Floating CSS Lava Sparks
interface LavaSpark {
  id: number
  x: number
  y: number
  size: number
  color: string
  duration: number
  delay: number
}

const lavaSparks = ref<LavaSpark[]>([])

function initLavaCanvas() {
  const canvas = lavaCanvasRef.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  if (!ctx) return

  const handleResize = () => {
    if (!canvas) return
    const w = window.innerWidth || 1400
    const h = window.innerHeight || 900
    canvas.width = w
    canvas.height = h
  }
  handleResize()
  window.addEventListener('resize', handleResize)

  // Rich Glowing Molten Magma Colors
  const lavaColors = [
    'rgba(234, 88, 12, 0.85)',  // Fiery Amber Lava
    'rgba(245, 158, 11, 0.75)',  // Molten Gold
    'rgba(225, 29, 72, 0.80)',   // Magma Crimson
    'rgba(185, 28, 28, 0.85)',   // Deep Red Lava
    'rgba(249, 115, 22, 0.90)'   // Bright Lava Heat
  ]

  // Create 18 rising molten lava blobs
  const blobs: LavaBlob[] = []
  for (let i = 0; i < 18; i++) {
    const baseR = Math.random() * 140 + 80
    blobs.push({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      radius: baseR,
      baseRadius: baseR,
      vy: -(Math.random() * 0.7 + 0.3),
      vx: Math.random() * 0.6 - 0.3,
      color: lavaColors[i % lavaColors.length],
      pulseOffset: Math.random() * Math.PI * 2,
      pulseSpeed: Math.random() * 0.02 + 0.008
    })
  }

  // Create 40 glowing lava embers
  const embers: LavaEmber[] = []
  const emberColors = ['#f97316', '#f59e0b', '#ef4444', '#fbeb8b', '#ffffff']
  for (let i = 0; i < 40; i++) {
    embers.push({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      size: Math.random() * 3.5 + 1,
      vy: -(Math.random() * 1.5 + 0.6),
      vx: Math.random() * 1.0 - 0.5,
      opacity: Math.random() * 0.8 + 0.2,
      color: emberColors[Math.floor(Math.random() * emberColors.length)]
    })
  }

  let time = 0

  const render = () => {
    if (!canvas || !ctx) return
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    time += 1

    const width = canvas.width
    const height = canvas.height

    // 1. Draw Molten Lava Heat Gradient at Base
    const baseGrad = ctx.createLinearGradient(0, height * 0.3, 0, height)
    baseGrad.addColorStop(0, 'rgba(0, 0, 0, 0)')
    baseGrad.addColorStop(0.5, 'rgba(185, 28, 28, 0.4)')
    baseGrad.addColorStop(1, 'rgba(234, 88, 12, 0.75)')
    ctx.fillStyle = baseGrad
    ctx.fillRect(0, 0, width, height)

    // 2. Render Morphing Lava Lamp Blobs
    blobs.forEach((b) => {
      b.y += b.vy
      b.x += b.vx + Math.sin(time * 0.025 + b.pulseOffset) * 0.6

      if (b.y + b.radius < -100) {
        b.y = height + b.radius + 50
        b.x = Math.random() * width
      }

      b.radius = b.baseRadius + Math.sin(time * b.pulseSpeed + b.pulseOffset) * 30

      const px = b.x + smoothedMouseX.value * 45
      const py = b.y + smoothedMouseY.value * 45

      ctx.beginPath()
      const grad = ctx.createRadialGradient(px, py, b.radius * 0.1, px, py, b.radius)
      grad.addColorStop(0, b.color)
      grad.addColorStop(0.6, b.color.replace(/[\d\.]+\)$/, '0.4)'))
      grad.addColorStop(1, 'rgba(0, 0, 0, 0)')

      ctx.fillStyle = grad
      ctx.arc(px, py, b.radius, 0, Math.PI * 2)
      ctx.fill()
    })

    // 3. Render Canvas Lava Embers
    embers.forEach((e) => {
      e.y += e.vy
      e.x += e.vx + Math.sin(time * 0.04 + e.size) * 0.7

      if (e.y < -20) {
        e.y = height + 20
        e.x = Math.random() * width
      }

      const px = e.x + smoothedMouseX.value * 30
      const py = e.y + smoothedMouseY.value * 30

      ctx.beginPath()
      ctx.fillStyle = e.color
      ctx.globalAlpha = e.opacity
      ctx.shadowBlur = 12
      ctx.shadowColor = e.color
      ctx.arc(px, py, e.size, 0, Math.PI * 2)
      ctx.fill()
      ctx.shadowBlur = 0
      ctx.globalAlpha = 1
    })

    lavaAnimationId = requestAnimationFrame(render)
  }

  render()
}

onMounted(() => {
  animationFrameId = requestAnimationFrame(updateParallax)
  initLavaCanvas()

  // Generate CSS floating lava ember sparks
  const sparkColors = ['#f97316', '#f59e0b', '#ef4444', '#ffffff', '#fbbf24']
  const items: LavaSpark[] = []
  for (let i = 0; i < 45; i++) {
    items.push({
      id: i,
      x: Math.random() * 100,
      y: Math.random() * 100,
      size: Math.random() * 4 + 1.5,
      color: sparkColors[Math.floor(Math.random() * sparkColors.length)],
      duration: Math.random() * 10 + 6,
      delay: Math.random() * 5
    })
  }
  lavaSparks.value = items
})

onUnmounted(() => {
  if (animationFrameId !== null) {
    cancelAnimationFrame(animationFrameId)
  }
  if (lavaAnimationId !== null) {
    cancelAnimationFrame(lavaAnimationId)
  }
})

function startDesigning() {
  router.push('/signup')
}
</script>


<template>
  <section
    ref="heroRef"
    @mousemove="handleHeroMouseMove"
    class="relative min-h-screen flex flex-col items-center justify-center px-6 pt-32 pb-20 z-10 text-[#f5f4f0] select-none overflow-hidden"
  >
    <!-- Dynamic Molten Lava Lamp Engine & Animated Luxury Background -->
    <div
      class="absolute inset-0 z-0 pointer-events-none overflow-hidden transition-colors duration-500"
      :style="{ backgroundColor: heroState.bgType === 'solid' ? heroState.solidBgColor : '#000000' }"
    >
      <!-- SOLID COLOR BACKGROUND MODE -->
      <template v-if="heroState.bgType === 'solid'">
        <div
          class="absolute inset-0 transition-colors duration-500"
          :style="{ backgroundColor: heroState.solidBgColor }"
        />
        <!-- Subtle Motion Grid overlay for texture elegance -->
        <div
          class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_40%,#000_70%,transparent_100%)]"
        />
        <!-- Bottom fade-out gradient -->
        <div
          class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-[#000000] via-transparent to-transparent"
        />
      </template>

      <!-- PRESET TEXTURE MODE (Including Theme 5 & Option 6) -->
      <template v-else>
        <!-- 1. Real-time Molten Lava Blobs Canvas Engine -->
        <canvas
          ref="lavaCanvasRef"
          class="absolute inset-0 w-full h-full filter blur-[40px] saturate-200 opacity-90 mix-blend-screen"
        />

        <!-- 2. Texture Background Image Layer (For Presets 1-5) -->
        <div
          v-if="heroState.bgImageUrl !== 'black-red-light-spots'"
          class="absolute inset-0 w-full h-full animate-lava-liquid-flow pointer-events-none"
          :style="{
            transform: `translate3d(${smoothedMouseX * 35}px, ${smoothedMouseY * 35}px, 0)`
          }"
        >
          <img
            :src="heroState.bgImageUrl"
            alt="Hero Texture Background"
            class="absolute -inset-16 w-[calc(100%+128px)] h-[calc(100%+128px)] max-w-none object-cover opacity-60 mix-blend-screen filter brightness-115 contrast-130 animate-lava-heat-pulse"
          />

          <div
            class="absolute -inset-16 w-[calc(100%+128px)] h-[calc(100%+128px)] bg-cover bg-center opacity-45 mix-blend-color-dodge filter brightness-125 saturate-180 animate-lava-liquid-flow-reverse"
            :style="{ backgroundImage: `url('${heroState.bgImageUrl}')` }"
          />
        </div>

        <!-- 3. Floating Red Ember Sparks Layer -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden z-1">
          <div
            v-for="s in lavaSparks"
            :key="s.id"
            class="absolute rounded-full animate-lava-spark-float"
            :style="{
              left: `${s.x}%`,
              top: `${s.y}%`,
              width: `${s.size}px`,
              height: `${s.size}px`,
              backgroundColor: s.color,
              boxShadow: `0 0 ${s.size * 4}px ${s.color}`,
              animationDuration: `${s.duration}s`,
              animationDelay: `${s.delay}s`
            }"
          />
        </div>

        <!-- 4. Drifting Glowing Dark Red Light Spots (5 Perfectly Distributed Orbs: 4 Corners + Dead Center) -->
        <div
          v-if="heroState.bgImageUrl === 'black-red-light-spots'"
          class="absolute inset-0 pointer-events-none overflow-hidden z-1"
          :style="{
            transform: `translate3d(${smoothedMouseX * 30}px, ${smoothedMouseY * 30}px, 0)`
          }"
        >
          <!-- Red Light Spot 1 (Far Top-Left Corner) -->
          <div
            class="absolute top-[2%] left-[2%] w-[320px] h-[320px] rounded-full blur-[45px] opacity-75 mix-blend-screen animate-red-spot-1 pointer-events-none"
            style="background: radial-gradient(circle, #b91c1c 0%, #7f1d1d 40%, rgba(99,10,10,0.3) 65%, transparent 75%);"
          />

          <!-- Red Light Spot 2 (Far Bottom-Right Corner) -->
          <div
            class="absolute bottom-[2%] right-[2%] w-[340px] h-[340px] rounded-full blur-[48px] opacity-80 mix-blend-screen animate-red-spot-2 pointer-events-none"
            style="background: radial-gradient(circle, #991b1b 0%, #6b0d0d 40%, rgba(80,8,8,0.3) 65%, transparent 75%);"
          />

          <!-- Red Light Spot 3 (Far Top-Right Corner) -->
          <div
            class="absolute top-[2%] right-[2%] w-[300px] h-[300px] rounded-full blur-[40px] opacity-70 mix-blend-screen animate-red-spot-3 pointer-events-none"
            style="background: radial-gradient(circle, #a11212 0%, #6e0a0a 40%, rgba(70,5,5,0.25) 65%, transparent 75%);"
          />

          <!-- Red Light Spot 4 (Far Bottom-Left Corner) -->
          <div
            class="absolute bottom-[2%] left-[2%] w-[310px] h-[310px] rounded-full blur-[42px] opacity-75 mix-blend-screen animate-red-spot-4 pointer-events-none"
            style="background: radial-gradient(circle, #aa1414 0%, #700c0c 40%, rgba(75,6,6,0.3) 65%, transparent 75%);"
          />

          <!-- Red Light Spot 5 (Dead Center Spotlight) -->
          <div
            class="absolute top-[48%] left-[50%] -translate-x-1/2 -translate-y-1/2 w-[340px] h-[340px] rounded-full blur-[46px] opacity-80 mix-blend-screen animate-red-spot-5 pointer-events-none"
            style="background: radial-gradient(circle, #b91c1c 0%, #7f1d1d 40%, rgba(99,10,10,0.3) 65%, transparent 75%);"
          />
        </div>

        <!-- 5. Vignette & Dark Overlay -->
        <div class="absolute inset-0 bg-black/45 pointer-events-none" />
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_20%,rgba(0,0,0,0.70)_70%,rgba(0,0,0,0.96)_100%)]" />
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.015)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.015)_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_40%,#000_70%,transparent_100%)]" />
        <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-[#000000] via-[#000000]/80 to-transparent" />
      </template>
    </div>

    <!-- Hero Contents with Dynamic Alignment -->
    <div
      class="relative z-10 w-full max-w-5xl flex flex-col transition-all duration-500"
      :class="[
        heroState.alignment === 'left' ? 'items-start text-left' :
        heroState.alignment === 'right' ? 'items-end text-right' :
        'items-center text-center'
      ]"
    >
      <div
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
        @mousemove="handleHeadlineMouseMove"
        v-reveal
        class="flex flex-col max-w-4xl mb-16 relative group/hero cursor-default reveal-up"
        :class="[
          heroState.alignment === 'left' ? 'items-start text-left' :
          heroState.alignment === 'right' ? 'items-end text-right' :
          'items-center text-center'
        ]"
      >
      <!-- Dynamic Pill Badge -->
      <div
        class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border text-[10px] font-mono tracking-widest uppercase mb-6 transition-all duration-300 shadow-sm"
        :style="{
          borderColor: `${heroState.themeColor}30`,
          backgroundColor: `${heroState.themeColor}10`,
          color: heroState.themeColor
        }"
      >
        <span class="w-1.5 h-1.5 rounded-full animate-pulse" :style="{ backgroundColor: heroState.themeColor }" />
        {{ heroState.badgeText }}
      </div>

      <!-- Headline with selection guide styling on hover -->
      <div class="relative px-6 py-4 transition-all duration-300">
        <!-- Interactive Spotlight Glow -->
        <div
          class="absolute -z-10 pointer-events-none rounded-full w-96 h-96 transition-opacity duration-500 opacity-0 group-hover/hero:opacity-100"
          :style="{
            left: `${headlineMouseX - 192}px`,
            top: `${headlineMouseY - 192}px`,
            background: `radial-gradient(circle, ${heroState.themeColor}35 0%, rgba(217, 119, 6, 0.08) 50%, transparent 70%)`
          }"
        />

        <!-- Vector Guides Outline -->
        <div
          class="absolute inset-0 border border-dashed border-red-500/0 rounded-lg pointer-events-none transition-all duration-300"
          :class="{ 'border-red-500/30 bg-red-500/[0.01] scale-[1.02]': isHovered }"
        >
          <!-- Corner handles -->
          <div v-if="isHovered" class="absolute -top-1 -left-1 w-2 h-2 bg-[#0e0d0b] border border-red-500 rounded-sm animate-pulse" />
          <div v-if="isHovered" class="absolute -top-1 -right-1 w-2 h-2 bg-[#0e0d0b] border border-red-500 rounded-sm animate-pulse" />
          <div v-if="isHovered" class="absolute -bottom-1 -left-1 w-2 h-2 bg-[#0e0d0b] border border-red-500 rounded-sm animate-pulse" />
          <div v-if="isHovered" class="absolute -bottom-1 -right-1 w-2 h-2 bg-[#0e0d0b] border border-red-500 rounded-sm animate-pulse" />
          
          <!-- Bounding box info badge -->
          <div
            v-if="isHovered"
            class="absolute -top-6 left-1/2 -translate-x-1/2 px-1.5 py-0.5 rounded bg-red-500 text-white text-[8px] font-mono tracking-wider uppercase shadow-md"
          >
            H1: Bounding Box : auto-layout
          </div>
        </div>

        <h1 class="text-5xl sm:text-7xl font-extrabold tracking-tight leading-[1.05] text-[#f5f4f0] max-w-3xl select-none">
          {{ heroState.headlineMain }}<br />
          <span
            class="text-transparent bg-clip-text transition-all duration-500 ease-out inline-block origin-center"
            :class="{ 'scale-105 filter drop-shadow-[0_0_20px_rgba(156,39,39,0.5)]': isHovered }"
            :style="{
              backgroundImage: `linear-gradient(to right, ${heroState.gradientFrom}, ${heroState.gradientVia}, ${heroState.gradientTo})`
            }"
          >
            {{ heroState.headlineGradient }}
          </span>
        </h1>
      </div>

      <p class="mt-6 text-base sm:text-lg text-[#a1a1aa] max-w-xl font-light leading-relaxed">
        {{ heroState.subtitle }}
      </p>

      <div v-reveal class="mt-8 flex flex-col sm:flex-row items-center gap-4 w-full sm:w-auto reveal-up delay-300">
        <button
          @click="startDesigning"
          type="button"
          class="w-full sm:w-auto px-8 py-3.5 rounded-xl font-bold text-xs tracking-widest font-mono shadow-md transition-all duration-200 hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2"
          :style="{
            backgroundColor: heroState.primaryBtnBg,
            color: heroState.primaryBtnTextColor
          }"
        >
          <span>{{ heroState.primaryBtnText }}</span>
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
        </button>

        <router-link
          to="/signup"
          class="w-full sm:w-auto px-8 py-3.5 rounded-xl border border-white/10 hover:border-white/20 font-bold text-xs tracking-widest font-mono transition-all duration-200 hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2"
          :style="{
            backgroundColor: heroState.secondaryBtnBg,
            color: heroState.secondaryBtnTextColor
          }"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
          </svg>
          <span>{{ heroState.secondaryBtnText }}</span>
        </router-link>


        <a
          href="#features"
          class="w-full sm:w-auto px-6 py-3.5 text-xs text-[#a1a1aa] hover:text-white font-medium transition-all duration-200 flex items-center justify-center"
        >
          Explore Features
        </a>
      </div>
    </div>


    <!-- Product Mockup Preview Container with subtle 3D tilt/depth -->
    <div v-reveal class="w-full max-w-5xl px-4 perspective-[1500px] reveal-up delay-400">
      <div
        class="w-full rounded-2xl shadow-2xl overflow-hidden transform-gpu rotate-x-[6deg] rotate-y-[-2deg] rotate-z-[1deg] hover:rotate-0 transition-transform duration-700 ease-out glass-card"
      >
        <!-- Mockup Header / Top bar -->
        <div class="flex items-center justify-between px-4 py-2 border-b border-white/5 bg-[#141210]/90 text-xs font-mono text-[#a1a1aa]">
          <!-- Window Controls -->
          <div class="flex items-center gap-1.5 w-1/4">
            <span class="w-2.5 h-2.5 rounded-full bg-[#ef4444]/20 border border-[#ef4444]/40" />
            <span class="w-2.5 h-2.5 rounded-full bg-[#f59e0b]/20 border border-[#f59e0b]/40" />
            <span class="w-2.5 h-2.5 rounded-full bg-[#10b981]/20 border border-[#10b981]/40" />
          </div>

          <!-- Active tab -->
          <div class="flex items-center gap-2 px-3 py-1 rounded bg-[#282624]/60 border border-white/5 text-[10px] font-semibold text-[#f5f4f0]">
            <span class="w-1.5 h-1.5 rounded-full bg-[#9c2727]" />
            landing-v2.nex
          </div>

          <!-- User profile avatars simulation -->
          <div class="flex items-center justify-end gap-1.5 w-1/4">
            <div class="flex -space-x-2">
              <span class="w-5 h-5 rounded-full border border-[#1c1a18] bg-blue-500 text-[8px] flex items-center justify-center font-bold text-white">JD</span>
              <span class="w-5 h-5 rounded-full border border-[#1c1a18] bg-amber-500 text-[8px] flex items-center justify-center font-bold text-white">MK</span>
            </div>
            <button
              @click="startDesigning"
              type="button"
              class="px-2.5 py-0.5 rounded bg-[#9c2727] text-white text-[9px] font-bold"
            >
              Share
            </button>
          </div>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center justify-center gap-1 py-1.5 border-b border-white/5 bg-[#141210]/90 shadow-sm">
          <div class="flex items-center gap-0.5 px-2 py-0.5 rounded-lg border border-white/5">
            <!-- Tool buttons mock -->
            <button type="button" class="p-1 rounded text-[#f5f4f0] bg-[#282624]"><icon-lucide-mouse-pointer class="size-3.5" /></button>
            <button type="button" class="p-1 rounded text-[#a1a1aa] hover:text-white"><icon-lucide-frame class="size-3.5" /></button>
            <button type="button" class="p-1 rounded text-[#a1a1aa] hover:text-white"><icon-lucide-square class="size-3.5" /></button>
            <button type="button" class="p-1 rounded text-[#a1a1aa] hover:text-white"><icon-lucide-pen-tool class="size-3.5" /></button>
            <button type="button" class="p-1 rounded text-[#a1a1aa] hover:text-white"><icon-lucide-type class="size-3.5" /></button>
            <button type="button" class="p-1 rounded text-[#a1a1aa] hover:text-white"><icon-lucide-hand class="size-3.5" /></button>
            <button type="button" class="p-1 rounded text-[#a1a1aa] hover:text-white"><icon-lucide-message-square class="size-3.5" /></button>
          </div>
        </div>

        <!-- Main Workspace Area -->
        <div class="flex h-[360px] overflow-hidden">
          <!-- Left Sidebar (Layers) -->
          <aside class="w-56 border-r border-white/5 glass-panel flex flex-col text-[11px] font-mono text-[#a1a1aa] select-none">
            <div class="px-3 py-2 font-bold border-b border-white/5 text-[#f5f4f0]">Layers</div>
            <div class="flex-1 p-2 overflow-y-auto space-y-1.5">
              <div class="flex items-center gap-1.5 text-[#f5f4f0] font-semibold"><icon-lucide-frame class="size-3 text-[#9c2727]" />Hero Section</div>
              <div class="pl-4 flex items-center gap-1.5"><icon-lucide-frame class="size-3" />Badge</div>
              <div class="pl-4 flex items-center gap-1.5"><icon-lucide-type class="size-3" />Headline Text</div>
              <div class="pl-4 flex items-center gap-1.5"><icon-lucide-frame class="size-3" />CTA Buttons</div>
              <div class="pl-8 flex items-center gap-1.5"><icon-lucide-square class="size-3" />Primary Btn</div>
              <div class="pl-8 flex items-center gap-1.5"><icon-lucide-square class="size-3" />Secondary Btn</div>
              <div class="flex items-center gap-1.5"><icon-lucide-frame class="size-3 text-[#9c2727]" />Features Section</div>
              <div class="pl-4 flex items-center gap-1.5"><icon-lucide-frame class="size-3" />Bento Grid</div>
            </div>
          </aside>

          <!-- Canvas Preview -->
          <main class="flex-1 bg-[#0e0d0b] relative flex items-center justify-center overflow-hidden">
            <!-- Grid pattern backdrop -->
            <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,0.06)_1px,transparent_1px)] [background-size:16px_16px] opacity-60" />

            <!-- Active design wireframe card -->
            <div class="relative w-80 p-5 rounded-xl border border-[#9c2727]/50 bg-[#1c1a18] shadow-md flex flex-col gap-4">
              <!-- Label -->
              <span class="absolute -top-2.5 -left-px px-2 py-0.5 rounded bg-[#9c2727] text-white text-[8px] font-bold tracking-wider font-mono">
                Frame: Active Card
              </span>

              <!-- Mock contents inside canvas card -->
              <div class="w-10 h-10 rounded-lg bg-[#9c2727]/10 flex items-center justify-center text-[#9c2727] font-extrabold text-sm">
                N
              </div>
              <div class="space-y-1.5">
                <div class="h-3 w-3/4 bg-white/15 rounded" />
                <div class="h-2 w-1/2 bg-white/10 rounded" />
              </div>

              <!-- Alignment guide simulation -->
              <div class="absolute -left-12 top-1/2 w-12 border-t border-dashed border-red-500" />
              <div class="absolute -right-12 top-1/2 w-12 border-t border-dashed border-red-500" />
              <div class="absolute left-1/2 -top-12 h-12 border-l border-dashed border-red-500" />
              <!-- Spacing badge -->
              <span class="absolute -left-8 top-1/2 -translate-y-1/2 px-1 rounded bg-red-500 text-white text-[8px] font-mono">
                48
              </span>

              <!-- Target handles -->
              <div class="absolute -top-1 -left-1 w-2 h-2 bg-white border border-[#9c2727]" />
              <div class="absolute -top-1 -right-1 w-2 h-2 bg-white border border-[#9c2727]" />
              <div class="absolute -bottom-1 -left-1 w-2 h-2 bg-white border border-[#9c2727]" />
              <div class="absolute -bottom-1 -right-1 w-2 h-2 bg-white border border-[#9c2727]" />
            </div>

            <!-- Custom Collaborator Cursors -->
            <div class="absolute top-1/3 left-1/4 pointer-events-none select-none flex items-center gap-1">
              <svg class="w-4 h-4 text-blue-500 drop-shadow-sm" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4 4l5 16 3-6 6-3z" />
              </svg>
              <span class="px-1.5 py-0.5 rounded bg-blue-500 text-white text-[8px] font-mono font-semibold">JD</span>
            </div>

            <div class="absolute bottom-1/4 right-1/3 pointer-events-none select-none flex items-center gap-1">
              <svg class="w-4 h-4 text-amber-500 drop-shadow-sm" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4 4l5 16 3-6 6-3z" />
              </svg>
              <span class="px-1.5 py-0.5 rounded bg-amber-500 text-white text-[8px] font-mono font-semibold">MK</span>
            </div>
          </main>

          <!-- Right Sidebar (Properties) -->
          <aside class="w-60 border-l border-white/5 glass-panel flex flex-col text-[11px] font-mono text-[#a1a1aa] select-none">
            <div class="px-3 py-2 font-bold border-b border-white/5 text-[#f5f4f0]">Properties</div>
            <div class="flex-1 p-3 overflow-y-auto space-y-4">
              <!-- Alignment section -->
              <div class="space-y-1.5">
                <div class="text-[9px] font-bold text-[#f5f4f0] uppercase tracking-wider">Align</div>
                <div class="grid grid-cols-6 gap-1">
                  <div class="h-6 rounded border border-white/5 bg-[#181614] flex items-center justify-center text-xs"><icon-lucide-align-left class="size-3" /></div>
                  <div class="h-6 rounded border border-white/5 bg-[#181614] flex items-center justify-center text-xs"><icon-lucide-align-center class="size-3" /></div>
                  <div class="h-6 rounded border border-white/5 bg-[#181614] flex items-center justify-center text-xs"><icon-lucide-align-right class="size-3" /></div>
                  <div class="h-6 rounded border border-white/5 bg-[#181614] flex items-center justify-center text-xs"><icon-lucide-align-vertical-distribute-center class="size-3" /></div>
                  <div class="h-6 rounded border border-white/5 bg-[#181614] flex items-center justify-center text-xs"><icon-lucide-align-horizontal-distribute-center class="size-3" /></div>
                  <div class="h-6 rounded border border-white/5 bg-[#181614] flex items-center justify-center text-xs"><icon-lucide-layout-grid class="size-3" /></div>
                </div>
              </div>

              <!-- Dimensions -->
              <div class="space-y-1.5">
                <div class="text-[9px] font-bold text-[#f5f4f0] uppercase tracking-wider">Layout</div>
                <div class="grid grid-cols-2 gap-2 text-[10px]">
                  <div class="flex items-center gap-1.5 border border-white/5 px-2 py-1 rounded bg-[#181614]">
                    <span class="text-[#a1a1aa]">W</span>
                    <span class="font-bold text-[#f5f4f0]">320</span>
                  </div>
                  <div class="flex items-center gap-1.5 border border-white/5 px-2 py-1 rounded bg-[#181614]">
                    <span class="text-[#a1a1aa]">H</span>
                    <span class="font-bold text-[#f5f4f0]">180</span>
                  </div>
                </div>
              </div>

              <!-- Typography -->
              <div class="space-y-1.5">
                <div class="text-[9px] font-bold text-[#f5f4f0] uppercase tracking-wider">Text</div>
                <div class="border border-white/5 px-2.5 py-1.5 rounded bg-[#181614] font-semibold text-[#f5f4f0] flex items-center justify-between">
                  <span>Geist Sans</span>
                  <icon-lucide-chevron-down class="size-3 text-[#a1a1aa]" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div class="border border-white/5 px-2 py-1 rounded bg-[#181614] text-[#f5f4f0]">14px</div>
                  <div class="border border-white/5 px-2 py-1 rounded bg-[#181614] text-[#f5f4f0]">Medium</div>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>
  </section>
</template>

<style scoped>
@keyframes lavaLiquidFlow {
  0% {
    transform: scale(1.06) translate(0%, 0%) rotate(0deg);
  }
  25% {
    transform: scale(1.14) translate(-2%, -1.5%) rotate(1.2deg);
  }
  50% {
    transform: scale(1.08) translate(1.8%, -2.2%) rotate(-0.8deg);
  }
  75% {
    transform: scale(1.15) translate(-1.5%, 1.8%) rotate(0.6deg);
  }
  100% {
    transform: scale(1.06) translate(0%, 0%) rotate(0deg);
  }
}

@keyframes lavaLiquidFlowReverse {
  0% {
    transform: scale(1.15) translate(0%, 0%) rotate(0deg);
  }
  25% {
    transform: scale(1.07) translate(1.8%, 1.8%) rotate(-1.2deg);
  }
  50% {
    transform: scale(1.14) translate(-2%, 1.5%) rotate(1deg);
  }
  75% {
    transform: scale(1.09) translate(1.5%, -1.8%) rotate(-0.5deg);
  }
  100% {
    transform: scale(1.15) translate(0%, 0%) rotate(0deg);
  }
}

@keyframes lavaHeatPulse {
  0%, 100% {
    filter: brightness(1.1) contrast(1.25) saturate(1.2);
  }
  50% {
    filter: brightness(1.3) contrast(1.35) saturate(1.5);
  }
}

@keyframes lavaSparkFloat {
  0% {
    transform: translateY(0px) translateX(0px) scale(0.8);
    opacity: 0.2;
  }
  30% {
    transform: translateY(-50px) translateX(15px) scale(1.4);
    opacity: 0.9;
  }
  70% {
    transform: translateY(-110px) translateX(-15px) scale(1.1);
    opacity: 0.6;
  }
  100% {
    transform: translateY(-160px) translateX(0px) scale(0.6);
    opacity: 0;
  }
}

@keyframes orbDrift1 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.45;
  }
  50% {
    transform: translate(110px, -70px) scale(1.35);
    opacity: 0.7;
  }
}

@keyframes orbDrift2 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.35;
  }
  50% {
    transform: translate(-120px, 80px) scale(1.4);
    opacity: 0.6;
  }
}

@keyframes redSpotMove1 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.75;
  }
  50% {
    transform: translate(130px, 95px) scale(1.25);
    opacity: 0.95;
  }
}

@keyframes redSpotMove2 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.8;
  }
  50% {
    transform: translate(-140px, -105px) scale(1.3);
    opacity: 0.95;
  }
}

@keyframes redSpotMove3 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.7;
  }
  50% {
    transform: translate(-120px, 90px) scale(1.2);
    opacity: 0.9;
  }
}

@keyframes redSpotMove4 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.75;
  }
  50% {
    transform: translate(125px, -100px) scale(1.25);
    opacity: 0.95;
  }
}

@keyframes redSpotMove5 {
  0%, 100% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 0.8;
  }
  50% {
    transform: translate(-50%, -50%) scale(1.35);
    opacity: 0.98;
  }
}

.animate-lava-liquid-flow {
  animation: lavaLiquidFlow 28s ease-in-out infinite;
  will-change: transform;
}

.animate-lava-liquid-flow-reverse {
  animation: lavaLiquidFlowReverse 34s ease-in-out infinite;
  will-change: transform;
}

.animate-lava-heat-pulse {
  animation: lavaHeatPulse 8s ease-in-out infinite;
  will-change: filter;
}

.animate-lava-spark-float {
  animation: lavaSparkFloat infinite ease-in-out;
  will-change: transform, opacity;
}

.animate-red-spot-1 {
  animation: redSpotMove1 7.2s ease-in-out infinite;
  will-change: transform, opacity;
}

.animate-red-spot-2 {
  animation: redSpotMove2 9.4s ease-in-out infinite;
  will-change: transform, opacity;
}

.animate-red-spot-3 {
  animation: redSpotMove3 8.1s ease-in-out infinite;
  will-change: transform, opacity;
}

.animate-red-spot-4 {
  animation: redSpotMove4 10.5s ease-in-out infinite;
  will-change: transform, opacity;
}

.animate-red-spot-5 {
  animation: redSpotMove5 6.3s ease-in-out infinite;
  will-change: transform, opacity;
}
</style>


