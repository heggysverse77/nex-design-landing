import { ref, computed } from 'vue'

const STORAGE_KEY = 'nex_global_user_theme_design_v11'

export interface HeroStyleState {
  badgeText: string
  badgeBgColor: string
  badgeTextColor: string
  badgeBorderColor: string
  headlineMain: string
  headlineGradient: string
  subtitle: string
  primaryBtnText: string
  primaryBtnBg: string
  primaryBtnTextColor: string
  secondaryBtnText: string
  secondaryBtnBg: string
  secondaryBtnTextColor: string
  themeColor: string
  gradientFrom: string
  gradientVia: string
  gradientTo: string
  backgroundMode: 'lava' | 'aurora' | 'emerald' | 'cyber'
  bgOpacity: number
  alignment: 'left' | 'center' | 'right'
  bgType: 'texture' | 'solid'
  bgImageUrl: string
  solidBgColor: string
}

// DEFAULT THEME: Obsidian Marble + Crimson Accent + Lava Embers
const defaultState: HeroStyleState = {
  badgeText: 'THE COLLABORATIVE DESIGN ENGINE',
  badgeBgColor: 'rgba(156, 39, 39, 0.1)',
  badgeTextColor: '#9c2727',
  badgeBorderColor: 'rgba(156, 39, 39, 0.3)',
  headlineMain: 'Design without',
  headlineGradient: 'limits.',
  subtitle: 'Nex Design is a powerful collaborative design platform built for creating interfaces, prototypes, design systems, and digital experiences in one place.',
  primaryBtnText: 'START DESIGNING FREE',
  primaryBtnBg: '#eae8e4',
  primaryBtnTextColor: '#121214',
  secondaryBtnText: 'DOWNLOAD DESKTOP',
  secondaryBtnBg: 'rgba(255, 255, 255, 0.08)',
  secondaryBtnTextColor: '#ffffff',
  themeColor: '#9c2727',
  gradientFrom: '#9c2727',
  gradientVia: '#c24141',
  gradientTo: '#ea580c',
  backgroundMode: 'lava',
  bgOpacity: 0.85,
  alignment: 'center',
  bgType: 'texture',
  bgImageUrl: '/luxury-bg.png', // Obsidian Marble (Default!)
  solidBgColor: '#0c0a09'
}

// Shared Singleton Reactive State
const heroState = ref<HeroStyleState>({ ...defaultState })
const isInitialized = ref(false)

// Curated Preset Textures (Including Option 6: Pure Black Background with Red Moving Light Spots)
export const bgPresets = [
  {
    id: 'luxury-obsidian',
    name: 'Obsidian Marble',
    desc: 'Dark luxury obsidian marble texture (Default)',
    url: '/luxury-bg.png',
    previewColor: '#1c1917'
  },
  {
    id: 'dark-cyber',
    name: 'Deep Cyber Mesh',
    desc: 'Futuristic blue cyber mesh texture',
    url: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=1200&q=80',
    previewColor: '#0f172a'
  },
  {
    id: 'abstract-waves',
    name: 'Abstract Fluid Waves',
    desc: 'Purple fluid wave dynamics',
    url: 'https://images.unsplash.com/photo-1634017839464-5c339ebe3cb4?auto=format&fit=crop&w=1200&q=80',
    previewColor: '#1e1b4b'
  },
  {
    id: 'golden-silk',
    name: 'Golden Silk Texture',
    desc: 'Warm luxury gold texture',
    url: 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?auto=format&fit=crop&w=1200&q=80',
    previewColor: '#451a03'
  },
  {
    id: 'fiery-red-magma',
    name: 'Fiery Red Magma Glow',
    desc: 'Rich glowing red magma texture (Fifth Option)',
    url: 'https://images.unsplash.com/photo-1541701494587-cb58502866ab?auto=format&fit=crop&w=1200&q=80',
    previewColor: '#7f1d1d'
  },
  {
    id: 'black-red-light-spots',
    name: 'Black Canvas & Red Light Spots',
    desc: 'Pure black background with moving red light spots (Sixth Option)',
    url: 'black-red-light-spots',
    previewColor: '#9c2727'
  }
]

// Preset Solid Colors
export const solidColorPresets = [
  { id: 'obsidian', name: 'Obsidian Black', color: '#000000' },
  { id: 'midnight', name: 'Midnight Charcoal', color: '#0c0a09' },
  { id: 'deep-burgundy', name: 'Deep Wine Burgundy', color: '#180808' },
  { id: 'dark-navy', name: 'Midnight Navy', color: '#090d16' },
  { id: 'forest-dark', name: 'Deep Forest', color: '#07120c' }
]

// Preset Theme Palettes
export const themePalettes = [
  { id: 'crimson', name: 'Nex Crimson', color: '#9c2727', from: '#9c2727', via: '#c24141', to: '#ea580c' },
  { id: 'amber', name: 'Fiery Amber', color: '#ea580c', from: '#ea580c', via: '#f59e0b', to: '#fbbf24' },
  { id: 'emerald', name: 'Emerald Wave', color: '#10b981', from: '#059669', via: '#10b981', to: '#34d399' },
  { id: 'cobalt', name: 'Cobalt Blue', color: '#3b82f6', from: '#2563eb', via: '#3b82f6', to: '#60a5fa' },
  { id: 'violet', name: 'Electric Purple', color: '#8b5cf6', from: '#7c3aed', via: '#8b5cf6', to: '#c084fc' }
]

export function useHeroCustomizer() {
  if (!isInitialized.value) {
    loadSavedStyle()
    isInitialized.value = true
  }

  function loadSavedStyle() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY)
      if (saved) {
        const parsed = JSON.parse(saved)
        heroState.value = { ...defaultState, ...parsed }
      }
    } catch (err) {
      console.error('Error loading hero style from cache', err)
    }
  }

  function applyAndSaveStyle(newState?: Partial<HeroStyleState>) {
    if (newState) {
      heroState.value = { ...heroState.value, ...newState }
    }
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(heroState.value))
    } catch (err) {
      console.error('Error saving hero style to cache', err)
    }
  }

  function setCustomColor(hexColor: string) {
    heroState.value.themeColor = hexColor
    heroState.value.badgeTextColor = hexColor
    heroState.value.badgeBorderColor = `${hexColor}40`
    heroState.value.badgeBgColor = `${hexColor}15`
    heroState.value.gradientFrom = hexColor
    heroState.value.gradientVia = hexColor
    heroState.value.gradientTo = adjustColorBrightness(hexColor, 40)
    applyAndSaveStyle()
  }

  function setGradientColors(from: string, via: string, to: string) {
    heroState.value.gradientFrom = from
    heroState.value.gradientVia = via
    heroState.value.gradientTo = to
    applyAndSaveStyle()
  }

  function setThemePalette(paletteId: string) {
    const p = themePalettes.find((item) => item.id === paletteId)
    if (!p) return
    heroState.value.themeColor = p.color
    heroState.value.badgeTextColor = p.color
    heroState.value.badgeBorderColor = `${p.color}40`
    heroState.value.badgeBgColor = `${p.color}15`
    heroState.value.gradientFrom = p.from
    heroState.value.gradientVia = p.via
    heroState.value.gradientTo = p.to
    applyAndSaveStyle()
  }

  function setBgImage(url: string) {
    heroState.value.bgType = 'texture'
    heroState.value.bgImageUrl = url
    applyAndSaveStyle()
  }

  function setSolidBgColor(color: string) {
    heroState.value.bgType = 'solid'
    heroState.value.solidBgColor = color
    applyAndSaveStyle()
  }

  function setAlignment(align: 'left' | 'center' | 'right') {
    heroState.value.alignment = align
    applyAndSaveStyle()
  }

  function resetToDefault() {
    heroState.value = { ...defaultState }
    try {
      localStorage.removeItem(STORAGE_KEY)
      localStorage.setItem(STORAGE_KEY, JSON.stringify(defaultState))
    } catch (err) {
      console.error('Error resetting hero cache', err)
    }
  }

  function adjustColorBrightness(hex: string, percent: number) {
    let num = parseInt(hex.replace('#', ''), 16)
    if (isNaN(num)) return hex
    let r = (num >> 16) + percent
    let g = ((num >> 8) & 0x00FF) + percent
    let b = (num & 0x0000FF) + percent
    r = Math.min(255, Math.max(0, r))
    g = Math.min(255, Math.max(0, g))
    b = Math.min(255, Math.max(0, b))
    return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`
  }

  const globalGradientStyle = computed(() => {
    return {
      backgroundImage: `linear-gradient(to right, ${heroState.value.gradientFrom}, ${heroState.value.gradientVia}, ${heroState.value.gradientTo})`
    }
  })

  const globalBadgeStyle = computed(() => {
    return {
      color: heroState.value.badgeTextColor,
      borderColor: heroState.value.badgeBorderColor,
      backgroundColor: heroState.value.badgeBgColor
    }
  })

  return {
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
    resetToDefault,
    globalGradientStyle,
    globalBadgeStyle
  }
}
