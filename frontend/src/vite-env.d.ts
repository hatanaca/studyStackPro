/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_URL: string
  readonly VITE_REVERB_APP_KEY: string
  readonly VITE_REVERB_ENABLED?: string
  readonly VITE_REVERB_HOST: string
  readonly VITE_REVERB_PORT: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
