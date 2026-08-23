<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useHeroCustomizer } from '@/composables/useHeroCustomizer'

const {
  heroState,
  themePalettes,
  bgPresets,
  solidColorPresets,
  applyAndSaveStyle,
  setCustomColor,
  setGradientColors,
  setThemePalette,
  setBgImage,
  setSolidBgColor,
  setAlignment,
  resetToDefault
} = useHeroCustomizer()

// 3D Canvas Orbit State
const orbitAngleX = ref(20)
const orbitAngleY = ref(-12)
const isOrbiting = ref(false)
const saveToastVisible = ref(false)
const activeSubElement = ref<'global_theme' | 'badge' | 'headline_main' | 'headline_gradient' | 'subtitle' | 'btn_primary' | 'btn_secondary' | 'background' | 'alignment'>('global_theme')

let lastMouseX = 0
let lastMouseY = 0

function handleCanvasMouseDown(e: MouseEvent) {
  if ((e.target as HTMLElement).closest('.hero-builder-card')) return
  isOrbiting.value = true
  lastMouseX = e.clientX
  lastMouseY = e.clientY
}

function handleCanvasMouseMove(e: MouseEvent) {
  if (!isOrbiting.value) return
  const deltaX = e.clientX - lastMouseX
  const deltaY = e.clientY - lastMouseY
  orbitAngleY.value += deltaX * 0.3
  orbitAngleX.value -= deltaY * 0.3
  lastMouseX = e.clientX
  lastMouseY = e.clientY
}

function handleCanvasMouseUp() {
  isOrbiting.value = false
}

// Apply & Save to Real Website
function handleSaveAndApply() {
  applyAndSaveStyle()
  saveToastVisible.value = true
  setTimeout(() => {
    saveToastVisible.value = false
  }, 3500)
}

function handleResetWebsite() {
  resetToDefault()
  saveToastVisible.value = true
  setTimeout(() => {
    saveToastVisible.value = false
  }, 2000)
}

// Collaborator Cursors
const cursor1 = ref({ x: 35, y: 30 })
const cursor2 = ref({ x: 65, y: 55 })
let cursorInterval: number | null = null

onMounted(() => {
  cursorInterval = window.setInterval(() => {
    cursor1.value = {
      x: 30 + Math.sin(Date.now() * 0.002) * 15,
      y: 25 + Math.cos(Date.now() * 0.0015) * 12
    }
    cursor2.value = {
      x: 60 + Math.cos(Date.now() * 0.0018) * 18,
      y: 50 + Math.sin(Date.now() * 0.0022) * 14
    }
  }, 50)
})

onUnmounted(() => {
  if (cursorInterval !== null) {
    clearInterval(cursorInterval)
  }
})
</script>

<template>
  <section id="canvas-reveal" class="relative py-20 px-4 sm:px-8 z-10 text-[#f5f4f0] bg-black/95 overflow-hidden select-none">
    
    <!-- Toast Notification for Real-Time Website Update -->
    <Transition name="fade">
      <div
        v-if="saveToastVisible"
        class="fixed top-6 right-6 z-[120] px-5 py-3 rounded-2xl bg-[#1c1a18] border border-emerald-500/50 text-white font-mono text-xs shadow-2xl flex items-center gap-3"
      >
        <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping" />
        <div>
          <div class="font-bold text-emerald-300">🚀 Website Style Applied Live!</div>
          <div class="text-[10px] text-[#a1a1aa]">Your changes have been saved and applied across the entire landing page.</div>
        </div>
      </div>
    </Transition>

    <!-- CLEAN & ELEGANT SECTION HEADER -->
    <div v-reveal class="text-center max-w-3xl mx-auto mb-10 reveal-up">
      <div
        class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full border text-[10px] font-mono tracking-widest uppercase mb-4 shadow-sm"
        :style="{ borderColor: `${heroState.themeColor}50`, backgroundColor: `${heroState.themeColor}15`, color: heroState.themeColor }"
      >
        <span class="w-1.5 h-1.5 rounded-full animate-pulse" :style="{ backgroundColor: heroState.themeColor }" />
        LIVE DESIGN STUDIO
      </div>

      <h2 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-[#f5f4f0] leading-tight">
        Customize Your Website<br />
        <span
          class="text-transparent bg-clip-text transition-all duration-500"
          :style="{ backgroundImage: `linear-gradient(to right, ${heroState.gradientFrom}, ${heroState.gradientVia}, ${heroState.gradientTo})` }"
        >
          In Real Time
        </span>
      </h2>

      <p class="mt-3 text-[#a1a1aa] text-sm sm:text-base font-light max-w-lg mx-auto leading-relaxed">
        Edit text, colors, buttons, and background themes below. Click <strong>Apply Live</strong> to transform the entire website.
      </p>

      <!-- Action Buttons Group -->
      <div class="mt-6 flex items-center justify-center gap-3">
        <button
          @click="handleSaveAndApply"
          type="button"
          class="px-6 py-2.5 rounded-xl font-mono text-xs font-bold tracking-wider transition-all shadow-lg flex items-center gap-2 transform hover:scale-105 active:scale-95 text-white cursor-pointer"
          :style="{ backgroundColor: heroState.themeColor }"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <span>🚀 APPLY LIVE CHANGES</span>
        </button>

        <button
          @click="handleResetWebsite"
          type="button"
          class="px-4 py-2.5 rounded-xl font-mono text-xs font-medium border border-white/15 bg-white/5 text-[#a1a1aa] hover:text-white hover:bg-white/10 transition-all cursor-pointer"
        >
          🔄 Reset Default
        </button>
      </div>
    </div>

    <!-- MAIN WORKSPACE WINDOW -->
    <div class="max-w-6xl mx-auto rounded-2xl border border-white/10 bg-[#121110] shadow-2xl overflow-hidden glass-card">
      
      <!-- Studio Top Toolbar -->
      <div class="flex flex-wrap items-center justify-between px-4 py-2.5 border-b border-white/10 bg-[#1c1a18] text-xs font-mono text-[#a1a1aa] select-none gap-2">
        
        <!-- Left: Brand Indicator -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-[#ef4444]/80" />
            <span class="w-2.5 h-2.5 rounded-full bg-[#f59e0b]/80" />
            <span class="w-2.5 h-2.5 rounded-full bg-[#10b981]/80" />
          </div>
          <div class="h-3.5 w-px bg-white/10 mx-1" />
          <div class="flex items-center gap-2 text-white font-semibold text-[11px]">
            <span class="w-2 h-2 rounded-full animate-pulse" :style="{ backgroundColor: heroState.themeColor }" />
            <span>nex-studio-builder.nex</span>
          </div>
        </div>

        <!-- Center: Quick Theme Presets -->
        <div class="flex items-center gap-2 bg-[#0e0d0b] border border-white/10 px-3 py-1 rounded-lg">
          <span class="text-[10px] text-[#a1a1aa] font-bold">Accent Theme:</span>
          <button
            v-for="p in themePalettes"
            :key="p.id"
            @click="setThemePalette(p.id)"
            type="button"
            class="w-4 h-4 rounded-full transition-transform hover:scale-125 border"
            :class="heroState.themeColor === p.color ? 'ring-2 ring-white scale-110 border-white' : 'border-transparent opacity-80 hover:opacity-100'"
            :style="{ backgroundColor: p.color }"
            :title="p.name"
          />
        </div>

        <!-- Right: Layout Alignment -->
        <div class="flex items-center gap-1.5 bg-[#0e0d0b] border border-white/10 px-2.5 py-1 rounded-lg text-[10px]">
          <span class="text-[#a1a1aa] font-bold">Align:</span>
          <button
            @click="setAlignment('left')"
            type="button"
            class="px-2 py-0.5 rounded transition-all"
            :class="heroState.alignment === 'left' ? 'bg-white text-black font-bold' : 'text-[#a1a1aa] hover:text-white'"
          >
            Left
          </button>
          <button
            @click="setAlignment('center')"
            type="button"
            class="px-2 py-0.5 rounded transition-all"
            :class="heroState.alignment === 'center' ? 'bg-white text-black font-bold' : 'text-[#a1a1aa] hover:text-white'"
          >
            Center
          </button>
          <button
            @click="setAlignment('right')"
            type="button"
            class="px-2 py-0.5 rounded transition-all"
            :class="heroState.alignment === 'right' ? 'bg-white text-black font-bold' : 'text-[#a1a1aa] hover:text-white'"
          >
            Right
          </button>
        </div>
      </div>

      <!-- MAIN WORKSPACE CANVAS LAYOUT -->
      <div class="flex flex-col md:flex-row min-h-[580px] relative overflow-hidden bg-[#0c0b0a]">
        
        <!-- LEFT SIDEBAR: COMPONENT SELECTOR -->
        <aside class="w-full md:w-56 border-r border-white/10 bg-[#161412] flex flex-col text-[11px] font-mono select-none z-20 shrink-0">
          <div class="px-3 py-2.5 font-bold border-b border-white/10 text-white flex items-center justify-between">
            <span>ELEMENTS</span>
            <span class="text-[9px] text-[#a1a1aa]">Click to edit</span>
          </div>

          <div class="p-2 space-y-1 overflow-y-auto">
            <button
              @click="activeSubElement = 'global_theme'"
              type="button"
              class="w-full flex items-center gap-2 p-2 rounded text-left transition-all border"
              :class="activeSubElement === 'global_theme' ? 'bg-white/15 text-white border-amber-400 font-bold' : 'text-[#a1a1aa] border-transparent hover:text-white hover:bg-white/5'"
            >
              <icon-lucide-palette class="size-3.5" :style="{ color: heroState.themeColor }" />
              <span>🎨 Global Theme & Spans</span>
            </button>

            <button
              @click="activeSubElement = 'background'"
              type="button"
              class="w-full flex items-center gap-2 p-2 rounded text-left transition-all border"
              :class="activeSubElement === 'background' ? 'bg-white/10 text-white border-amber-400 font-bold' : 'text-[#a1a1aa] border-transparent hover:text-white hover:bg-white/5'"
            >
              <icon-lucide-image class="size-3 text-cyan-400" />
              <span>🖼️ Background Theme</span>
            </button>

            <button
              @click="activeSubElement = 'badge'"
              type="button"
              class="w-full flex items-center gap-2 p-2 rounded text-left transition-all border"
              :class="activeSubElement === 'badge' ? 'bg-white/10 text-white border-amber-400 font-bold' : 'text-[#a1a1aa] border-transparent hover:text-white hover:bg-white/5'"
            >
              <icon-lucide-tag class="size-3 text-emerald-400" />
              <span>🏷️ Pill Badge</span>
            </button>

            <button
              @click="activeSubElement = 'headline_main'"
              type="button"
              class="w-full flex items-center gap-2 p-2 rounded text-left transition-all border"
              :class="activeSubElement === 'headline_main' ? 'bg-white/10 text-white border-amber-400 font-bold' : 'text-[#a1a1aa] border-transparent hover:text-white hover:bg-white/5'"
            >
              <icon-lucide-type class="size-3 text-blue-400" />
              <span>✏️ Headline Line 1</span>
            </button>

            <button
              @click="activeSubElement = 'headline_gradient'"
              type="button"
              class="w-full flex items-center gap-2 p-2 rounded text-left transition-all border"
              :class="activeSubElement === 'headline_gradient' ? 'bg-white/10 text-white border-amber-400 font-bold' : 'text-[#a1a1aa] border-transparent hover:text-white hover:bg-white/5'"
            >
              <icon-lucide-sparkles class="size-3 text-amber-400" />
              <span>✨ Gradient Word</span>
            </button>

            <button
              @click="activeSubElement = 'subtitle'"
              type="button"
              class="w-full flex items-center gap-2 p-2 rounded text-left transition-all border"
              :class="activeSubElement === 'subtitle' ? 'bg-white/10 text-white border-amber-400 font-bold' : 'text-[#a1a1aa] border-transparent hover:text-white hover:bg-white/5'"
            >
              <icon-lucide-align-left class="size-3 text-slate-400" />
              <span>📝 Subtitle</span>
            </button>

            <button
              @click="activeSubElement = 'btn_primary'"
              type="button"
              class="w-full flex items-center gap-2 p-2 rounded text-left transition-all border"
              :class="activeSubElement === 'btn_primary' ? 'bg-white/10 text-white border-amber-400 font-bold' : 'text-[#a1a1aa] border-transparent hover:text-white hover:bg-white/5'"
            >
              <icon-lucide-square class="size-3 text-emerald-400" />
              <span>🔘 Primary Button</span>
            </button>

            <button
              @click="activeSubElement = 'btn_secondary'"
              type="button"
              class="w-full flex items-center gap-2 p-2 rounded text-left transition-all border"
              :class="activeSubElement === 'btn_secondary' ? 'bg-white/10 text-white border-amber-400 font-bold' : 'text-[#a1a1aa] border-transparent hover:text-white hover:bg-white/5'"
            >
              <icon-lucide-square class="size-3 text-purple-400" />
              <span>🔘 Secondary Button</span>
            </button>
          </div>
        </aside>

        <!-- CENTER WORKSPACE CANVAS: 3D PREVIEW FRAME -->
        <main
          @mousedown="handleCanvasMouseDown"
          @mousemove="handleCanvasMouseMove"
          @mouseup="handleCanvasMouseUp"
          class="flex-1 relative flex items-center justify-center p-4 lg:p-8 perspective-[1800px] overflow-hidden cursor-grab active:cursor-grabbing min-h-[460px]"
          style="background: radial-gradient(circle at 50% 50%, #2a0b0b 0%, #080707 80%);"
        >
          <!-- Grid Backdrop -->
          <div class="absolute inset-0 bg-[radial-gradient(rgba(255,255,255,0.08)_1.5px,transparent_1.5px)] [background-size:20px_20px] opacity-70 pointer-events-none" />

          <!-- Canvas Hint -->
          <div class="absolute top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-[#1c1a18]/90 border border-white/10 text-[10px] font-mono text-[#a1a1aa] pointer-events-none flex items-center gap-2 shadow z-30">
            <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: heroState.themeColor }" />
            <span>Drag mouse to orbit 3D frame</span>
          </div>

          <!-- Collaborator Cursors -->
          <div
            class="absolute z-40 transition-all duration-300 pointer-events-none flex items-center gap-1"
            :style="{ left: `${cursor1.x}%`, top: `${cursor1.y}%` }"
          >
            <svg class="w-4 h-4 text-blue-500 drop-shadow-md" fill="currentColor" viewBox="0 0 24 24">
              <path d="M4 4l5 16 3-6 6-3z" />
            </svg>
            <span class="px-1.5 py-0.5 rounded bg-blue-500 text-white text-[9px] font-mono font-bold shadow">JD (Editing Theme)</span>
          </div>

          <!-- 3D HERO BUILDER CANVAS FRAME -->
          <div
            class="hero-builder-card relative w-full max-w-xl rounded-2xl border bg-[#1c1a18] p-7 shadow-2xl transition-all duration-300 ease-out transform-gpu flex flex-col space-y-5 overflow-hidden"
            :class="[
              heroState.alignment === 'left' ? 'items-start text-left' :
              heroState.alignment === 'right' ? 'items-end text-right' :
              'items-center text-center'
            ]"
            :style="{
              borderColor: heroState.themeColor,
              backgroundColor: heroState.bgType === 'solid' ? heroState.solidBgColor : '#1c1a18',
              transform: `rotateX(${orbitAngleX}deg) rotateY(${orbitAngleY}deg) scale(0.85)`
            }"
          >
            <!-- Background Texture & 5 Red Light Spots Overlay inside Studio Card Preview -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden rounded-2xl z-0">
              <div v-if="heroState.bgImageUrl === 'black-red-light-spots'" class="absolute inset-0 bg-black" />

              <div
                v-if="heroState.bgType === 'texture' && heroState.bgImageUrl !== 'black-red-light-spots'"
                class="absolute inset-0 opacity-40 bg-cover bg-center"
                :style="{ backgroundImage: `url('${heroState.bgImageUrl}')` }"
              />

              <!-- 5 Animated Moving Red Light Spots inside Studio Preview Card (ONLY for Option 6: 4 Corners + Center) -->
              <div v-if="heroState.bgImageUrl === 'black-red-light-spots'" class="absolute inset-0 overflow-hidden pointer-events-none opacity-85">
                <!-- Spot 1 (Far Top-Left Corner) -->
                <div
                  class="absolute top-[2%] left-[2%] w-[160px] h-[160px] rounded-full blur-[25px] opacity-80 mix-blend-screen animate-red-spot-1"
                  style="background: radial-gradient(circle, #b91c1c 0%, #7f1d1d 40%, rgba(99,10,10,0.3) 65%, transparent 75%);"
                />
                <!-- Spot 2 (Far Bottom-Right Corner) -->
                <div
                  class="absolute bottom-[2%] right-[2%] w-[170px] h-[170px] rounded-full blur-[28px] opacity-80 mix-blend-screen animate-red-spot-2"
                  style="background: radial-gradient(circle, #991b1b 0%, #6b0d0d 40%, rgba(80,8,8,0.3) 65%, transparent 75%);"
                />
                <!-- Spot 3 (Far Top-Right Corner) -->
                <div
                  class="absolute top-[2%] right-[2%] w-[150px] h-[150px] rounded-full blur-[22px] opacity-75 mix-blend-screen animate-red-spot-3"
                  style="background: radial-gradient(circle, #a11212 0%, #6e0a0a 40%, rgba(70,5,5,0.25) 65%, transparent 75%);"
                />
                <!-- Spot 4 (Far Bottom-Left Corner) -->
                <div
                  class="absolute bottom-[2%] left-[2%] w-[155px] h-[155px] rounded-full blur-[24px] opacity-80 mix-blend-screen animate-red-spot-4"
                  style="background: radial-gradient(circle, #aa1414 0%, #700c0c 40%, rgba(75,6,6,0.3) 65%, transparent 75%);"
                />
                <!-- Spot 5 (Dead Center Spotlight) -->
                <div
                  class="absolute top-[48%] left-[50%] -translate-x-1/2 -translate-y-1/2 w-[175px] h-[175px] rounded-full blur-[26px] opacity-85 mix-blend-screen animate-red-spot-5"
                  style="background: radial-gradient(circle, #b91c1c 0%, #7f1d1d 40%, rgba(99,10,10,0.3) 65%, transparent 75%);"
                />
              </div>
            </div>

            <!-- Frame Label -->
            <span class="absolute -top-3.5 -left-1 px-3 py-0.5 rounded text-white text-[9px] font-mono font-bold uppercase shadow z-10" :style="{ backgroundColor: heroState.themeColor }">
              ❖ Hero Frame ({{ heroState.alignment.toUpperCase() }})
            </span>

            <!-- Vector handles -->
            <div class="absolute -top-1 -left-1 w-2.5 h-2.5 bg-white border z-10" :style="{ borderColor: heroState.themeColor }" />
            <div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-white border z-10" :style="{ borderColor: heroState.themeColor }" />
            <div class="absolute -bottom-1 -left-1 w-2.5 h-2.5 bg-white border z-10" :style="{ borderColor: heroState.themeColor }" />
            <div class="absolute -bottom-1 -right-1 w-2.5 h-2.5 bg-white border z-10" :style="{ borderColor: heroState.themeColor }" />

            <!-- Pill Badge Element -->
            <div
              @click.stop="activeSubElement = 'badge'"
              class="relative z-10 inline-flex items-center gap-2 px-3 py-1 rounded-full border text-[10px] font-mono tracking-widest uppercase cursor-pointer hover:ring-2 hover:ring-amber-400 transition-all shadow-sm"
              :class="{ 'ring-2 ring-amber-400': activeSubElement === 'badge' }"
              :style="{
                borderColor: heroState.badgeBorderColor,
                backgroundColor: heroState.badgeBgColor,
                color: heroState.badgeTextColor
              }"
            >
              <span class="w-1.5 h-1.5 rounded-full animate-pulse" :style="{ backgroundColor: heroState.badgeTextColor }" />
              {{ heroState.badgeText }}
            </div>

            <!-- Headline Main & Gradient Highlight Element -->
            <h1 class="relative z-10 text-3xl sm:text-4xl font-extrabold text-white leading-tight cursor-pointer">
              <span
                @click.stop="activeSubElement = 'headline_main'"
                class="hover:underline p-1"
                :class="{ 'ring-2 ring-amber-400 bg-white/10 rounded': activeSubElement === 'headline_main' }"
              >
                {{ heroState.headlineMain }}
              </span><br />
              <span
                @click.stop="activeSubElement = 'headline_gradient'"
                class="text-transparent bg-clip-text p-1 inline-block"
                :class="{ 'ring-2 ring-amber-400 rounded': activeSubElement === 'headline_gradient' }"
                :style="{
                  backgroundImage: `linear-gradient(to right, ${heroState.gradientFrom}, ${heroState.gradientVia}, ${heroState.gradientTo})`
                }"
              >
                {{ heroState.headlineGradient }}
              </span>
            </h1>

            <!-- Subtitle Description Element -->
            <p
              @click.stop="activeSubElement = 'subtitle'"
              class="relative z-10 text-xs text-[#a1a1aa] max-w-md cursor-pointer p-1.5 hover:ring-1 hover:ring-amber-400 rounded leading-relaxed"
              :class="{ 'ring-2 ring-amber-400 bg-white/10': activeSubElement === 'subtitle' }"
            >
              {{ heroState.subtitle }}
            </p>

            <!-- Interactive Editable Buttons Elements -->
            <div class="relative z-10 flex gap-3 pt-1">
              <button
                @click.stop="activeSubElement = 'btn_primary'"
                type="button"
                class="px-5 py-2.5 rounded-xl text-xs font-bold font-mono transition-all hover:ring-2 hover:ring-amber-400 shadow-md cursor-pointer"
                :class="{ 'ring-2 ring-amber-400': activeSubElement === 'btn_primary' }"
                :style="{
                  backgroundColor: heroState.primaryBtnBg,
                  color: heroState.primaryBtnTextColor
                }"
              >
                {{ heroState.primaryBtnText }}
              </button>

              <button
                @click.stop="activeSubElement = 'btn_secondary'"
                type="button"
                class="px-5 py-2.5 rounded-xl border border-white/20 text-xs font-mono transition-all hover:ring-2 hover:ring-amber-400 shadow-md cursor-pointer"
                :class="{ 'ring-2 ring-amber-400': activeSubElement === 'btn_secondary' }"
                :style="{
                  backgroundColor: heroState.secondaryBtnBg,
                  color: heroState.secondaryBtnTextColor
                }"
              >
                {{ heroState.secondaryBtnText }}
              </button>
            </div>

          </div>
        </main>

        <!-- RIGHT SIDEBAR: INSPECTOR & CONTROLS -->
        <aside class="w-full md:w-72 border-l border-white/10 bg-[#161412] flex flex-col text-[11px] font-mono select-none z-20 shrink-0">
          <div class="px-3 py-2.5 font-bold border-b border-white/10 text-white flex items-center justify-between">
            <span>INSPECTOR</span>
            <span class="text-[9px] font-bold uppercase" :style="{ color: heroState.themeColor }">{{ activeSubElement.replace('_', ' ') }}</span>
          </div>

          <div class="p-3 space-y-4 overflow-y-auto">
            
            <!-- GLOBAL THEME CONTROLS -->
            <div v-if="activeSubElement === 'global_theme' || activeSubElement === 'headline_gradient'" class="space-y-3">
              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">🎨 Primary Accent Theme Color:</label>
                <div class="flex items-center gap-2">
                  <input
                    type="color"
                    :value="heroState.themeColor"
                    @input="setCustomColor(($event.target as HTMLInputElement).value)"
                    class="w-8 h-8 rounded border border-white/20 cursor-pointer bg-transparent"
                  />
                  <input
                    :value="heroState.themeColor"
                    @input="setCustomColor(($event.target as HTMLInputElement).value)"
                    type="text"
                    class="flex-1 px-2 py-1 rounded bg-[#242220] border border-white/10 text-white text-xs font-mono"
                  />
                </div>
              </div>

              <!-- Gradient Stop Pickers -->
              <div class="space-y-1.5 border-t border-white/10 pt-3">
                <label class="text-[9px] text-[#a1a1aa] font-bold">✨ Text Gradient Highlight Stops:</label>
                <div class="grid grid-cols-3 gap-2">
                  <div class="flex flex-col items-center">
                    <span class="text-[8px] text-[#a1a1aa] mb-1">From</span>
                    <input
                      type="color"
                      v-model="heroState.gradientFrom"
                      class="w-7 h-7 rounded border border-white/20 cursor-pointer bg-transparent"
                    />
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="text-[8px] text-[#a1a1aa] mb-1">Via</span>
                    <input
                      type="color"
                      v-model="heroState.gradientVia"
                      class="w-7 h-7 rounded border border-white/20 cursor-pointer bg-transparent"
                    />
                  </div>
                  <div class="flex flex-col items-center">
                    <span class="text-[8px] text-[#a1a1aa] mb-1">To</span>
                    <input
                      type="color"
                      v-model="heroState.gradientTo"
                      class="w-7 h-7 rounded border border-white/20 cursor-pointer bg-transparent"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- BACKGROUND CONTROLS (SOLID COLOR & PRESET TEXTURES) -->
            <div v-if="activeSubElement === 'background'" class="space-y-4">
              
              <!-- 1. Background Mode Switcher -->
              <div class="space-y-1.5">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Background Type:</label>
                <div class="grid grid-cols-2 gap-1 bg-[#0e0d0b] p-1 rounded-lg border border-white/10">
                  <button
                    @click="heroState.bgType = 'texture'"
                    type="button"
                    class="py-1 rounded text-[10px] font-bold transition-all"
                    :class="heroState.bgType === 'texture' ? 'bg-white text-black' : 'text-[#a1a1aa] hover:text-white'"
                  >
                    Preset Texture
                  </button>
                  <button
                    @click="heroState.bgType = 'solid'"
                    type="button"
                    class="py-1 rounded text-[10px] font-bold transition-all"
                    :class="heroState.bgType === 'solid' ? 'bg-white text-black' : 'text-[#a1a1aa] hover:text-white'"
                  >
                    Solid Color
                  </button>
                </div>
              </div>

              <!-- Solid Background Color Selector -->
              <div v-if="heroState.bgType === 'solid'" class="space-y-3 border-t border-white/10 pt-3">
                <div class="space-y-1">
                  <label class="text-[10px] text-[#a1a1aa] font-bold">Pick Solid Background Color:</label>
                  <div class="flex items-center gap-2">
                    <input
                      type="color"
                      v-model="heroState.solidBgColor"
                      @input="setSolidBgColor(($event.target as HTMLInputElement).value)"
                      class="w-8 h-8 rounded border border-white/20 cursor-pointer bg-transparent"
                    />
                    <input
                      v-model="heroState.solidBgColor"
                      @change="setSolidBgColor(heroState.solidBgColor)"
                      type="text"
                      class="flex-1 px-2 py-1 rounded bg-[#242220] border border-white/10 text-white text-xs font-mono"
                    />
                  </div>
                </div>

                <div class="space-y-1">
                  <label class="text-[9px] text-[#a1a1aa] font-bold">Preset Dark Solid Swatches:</label>
                  <div class="grid grid-cols-5 gap-1.5">
                    <button
                      v-for="s in solidColorPresets"
                      :key="s.id"
                      @click="setSolidBgColor(s.color)"
                      type="button"
                      class="w-7 h-7 rounded border transition-transform hover:scale-110"
                      :class="heroState.solidBgColor === s.color ? 'ring-2 ring-white border-white scale-105' : 'border-white/20'"
                      :style="{ backgroundColor: s.color }"
                      :title="s.name"
                    />
                  </div>
                </div>
              </div>

              <!-- Preset Textures Grid -->
              <div v-if="heroState.bgType === 'texture'" class="space-y-2 border-t border-white/10 pt-3">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Curated Texture Presets:</label>
                <div class="grid grid-cols-2 gap-1.5">
                  <button
                    v-for="bg in bgPresets"
                    :key="bg.id"
                    @click="setBgImage(bg.url)"
                    type="button"
                    class="p-2 rounded border text-left text-[9px] font-mono transition-all truncate"
                    :class="heroState.bgImageUrl === bg.url && heroState.bgType === 'texture' ? 'bg-white/20 border-white text-white font-bold' : 'bg-[#242220] border-white/10 text-[#a1a1aa] hover:text-white'"
                  >
                    {{ bg.name }}
                  </button>
                </div>
              </div>
            </div>

            <!-- BADGE CONTROLS -->
            <div v-if="activeSubElement === 'badge'" class="space-y-3">
              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Badge Text Label:</label>
                <input
                  v-model="heroState.badgeText"
                  type="text"
                  class="w-full px-2.5 py-1.5 rounded bg-[#242220] border border-white/10 text-white text-xs font-bold"
                />
              </div>

              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Badge Text Color:</label>
                <div class="flex items-center gap-2">
                  <input
                    type="color"
                    v-model="heroState.badgeTextColor"
                    class="w-7 h-7 rounded border border-white/20 cursor-pointer bg-transparent"
                  />
                  <input
                    v-model="heroState.badgeTextColor"
                    type="text"
                    class="flex-1 px-2 py-1 rounded bg-[#242220] border border-white/10 text-white text-[10px] font-mono"
                  />
                </div>
              </div>
            </div>

            <!-- HEADLINE MAIN CONTROLS -->
            <div v-if="activeSubElement === 'headline_main'" class="space-y-1.5">
              <label class="text-[10px] text-[#a1a1aa] font-bold">Headline Main Text (Line 1):</label>
              <input
                v-model="heroState.headlineMain"
                type="text"
                class="w-full px-2.5 py-1.5 rounded bg-[#242220] border border-white/10 text-white text-xs font-bold"
              />
            </div>

            <!-- SUBTITLE CONTROLS -->
            <div v-if="activeSubElement === 'subtitle'" class="space-y-1.5">
              <label class="text-[10px] text-[#a1a1aa] font-bold">Subtitle Description:</label>
              <textarea
                v-model="heroState.subtitle"
                rows="4"
                class="w-full px-2.5 py-1.5 rounded bg-[#242220] border border-white/10 text-white text-xs"
              />
            </div>

            <!-- PRIMARY BUTTON CONTROLS -->
            <div v-if="activeSubElement === 'btn_primary'" class="space-y-3">
              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Button Text Label:</label>
                <input
                  v-model="heroState.primaryBtnText"
                  type="text"
                  class="w-full px-2.5 py-1.5 rounded bg-[#242220] border border-white/10 text-white text-xs font-bold"
                />
              </div>

              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Background Fill Color:</label>
                <div class="flex items-center gap-2">
                  <input
                    type="color"
                    v-model="heroState.primaryBtnBg"
                    class="w-7 h-7 rounded border border-white/20 cursor-pointer bg-transparent"
                  />
                  <input
                    v-model="heroState.primaryBtnBg"
                    type="text"
                    class="flex-1 px-2 py-1 rounded bg-[#242220] border border-white/10 text-white text-xs font-mono"
                  />
                </div>
              </div>

              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Text Color:</label>
                <div class="flex items-center gap-2">
                  <input
                    type="color"
                    v-model="heroState.primaryBtnTextColor"
                    class="w-7 h-7 rounded border border-white/20 cursor-pointer bg-transparent"
                  />
                  <input
                    v-model="heroState.primaryBtnTextColor"
                    type="text"
                    class="flex-1 px-2 py-1 rounded bg-[#242220] border border-white/10 text-white text-xs font-mono"
                  />
                </div>
              </div>
            </div>

            <!-- SECONDARY BUTTON CONTROLS -->
            <div v-if="activeSubElement === 'btn_secondary'" class="space-y-3">
              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Button Text Label:</label>
                <input
                  v-model="heroState.secondaryBtnText"
                  type="text"
                  class="w-full px-2.5 py-1.5 rounded bg-[#242220] border border-white/10 text-white text-xs font-bold"
                />
              </div>

              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Background Fill Color:</label>
                <div class="flex items-center gap-2">
                  <input
                    type="color"
                    v-model="heroState.secondaryBtnBg"
                    class="w-7 h-7 rounded border border-white/20 cursor-pointer bg-transparent"
                  />
                  <input
                    v-model="heroState.secondaryBtnBg"
                    type="text"
                    class="flex-1 px-2 py-1 rounded bg-[#242220] border border-white/10 text-white text-xs font-mono"
                  />
                </div>
              </div>

              <div class="space-y-1">
                <label class="text-[10px] text-[#a1a1aa] font-bold">Text Color:</label>
                <div class="flex items-center gap-2">
                  <input
                    type="color"
                    v-model="heroState.secondaryBtnTextColor"
                    class="w-7 h-7 rounded border border-white/20 cursor-pointer bg-transparent"
                  />
                  <input
                    v-model="heroState.secondaryBtnTextColor"
                    type="text"
                    class="flex-1 px-2 py-1 rounded bg-[#242220] border border-white/10 text-white text-xs font-mono"
                  />
                </div>
              </div>
            </div>

            <!-- SAVE & APPLY BUTTON -->
            <div class="border-t border-white/10 pt-3">
              <button
                @click="handleSaveAndApply"
                type="button"
                class="w-full py-2.5 rounded-xl font-mono text-xs font-bold tracking-wider text-white shadow-lg transition-transform hover:scale-[1.02] active:scale-95 cursor-pointer flex items-center justify-center gap-2"
                :style="{ backgroundColor: heroState.themeColor }"
              >
                <span>🚀 APPLY LIVE</span>
              </button>
            </div>

          </div>
        </aside>

      </div>
    </div>
  </section>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.4s ease, transform 0.4s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@keyframes redSpotMove1 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.75;
  }
  50% {
    transform: translate(65px, 45px) scale(1.25);
    opacity: 0.95;
  }
}

@keyframes redSpotMove2 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.8;
  }
  50% {
    transform: translate(-70px, -50px) scale(1.3);
    opacity: 0.95;
  }
}

@keyframes redSpotMove3 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.7;
  }
  50% {
    transform: translate(-60px, 45px) scale(1.2);
    opacity: 0.9;
  }
}

@keyframes redSpotMove4 {
  0%, 100% {
    transform: translate(0px, 0px) scale(1);
    opacity: 0.75;
  }
  50% {
    transform: translate(60px, -50px) scale(1.25);
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
