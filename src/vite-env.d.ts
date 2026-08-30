/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_NEXDESIGN_ACCOUNT_API?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
