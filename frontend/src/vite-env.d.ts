/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_URL: string
  readonly VITE_REVERB_APP_KEY: string
  readonly VITE_REVERB_ENABLED?: string
  readonly VITE_REVERB_HOST: string
  readonly VITE_REVERB_PORT: string
  readonly VITE_REVERB_SCHEME?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

interface Window {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  Pusher: any
}
