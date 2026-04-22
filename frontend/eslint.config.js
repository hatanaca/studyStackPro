import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import tseslint from 'typescript-eslint'
import eslintConfigPrettier from 'eslint-config-prettier'

export default [
  { ignores: ['dist/**', 'node_modules/**', 'coverage/**', '**/*.config.ts', '**/*.config.js'] },
  ...tseslint.config(
    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
    languageOptions: {
      parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
        parser: '@typescript-eslint/parser',
        extraFileExtensions: ['.vue']
      },
    globals: {
        defineProps: 'readonly',
        defineEmits: 'readonly',
        defineExpose: 'readonly',
        withDefaults: 'readonly',
        window: 'readonly',
        document: 'readonly',
        setTimeout: 'readonly',
        clearTimeout: 'readonly',
        setInterval: 'readonly',
        clearInterval: 'readonly',
        console: 'readonly',
        Event: 'readonly',
        HTMLFormElement: 'readonly',
        FormData: 'readonly',
        confirm: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        HTMLElement: 'readonly',
        HTMLImageElement: 'readonly',
        Node: 'readonly',
        MouseEvent: 'readonly',
        KeyboardEvent: 'readonly',
        requestAnimationFrame: 'readonly',
        navigator: 'readonly',
        crypto: 'readonly',
        Blob: 'readonly',
        URL: 'readonly',
        // NOVAS ADIÇÕES:
        fetch: 'readonly',
        EventListener: 'readonly',
        CustomEvent: 'readonly',
        HTMLInputElement: 'readonly',
        FileReader: 'readonly',
        PointerEvent: 'readonly',
        File: 'readonly'
}
    }
  }
  ),
  {
    files: ['src/components/ui/Callout.vue', 'src/components/ui/Divider.vue'],
    rules: { 'vue/multi-word-component-names': 'off' },
  },
  eslintConfigPrettier,
]
