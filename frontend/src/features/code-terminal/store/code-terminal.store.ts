import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { ProgrammingLanguage, TerminalEntry } from '../types/code-terminal.types'

export const useCodeTerminalStore = defineStore('code-terminal', () => {
  const code = ref('console.log("Hello, World!")')
  const language = ref<ProgrammingLanguage>('javascript')
  const history = ref<TerminalEntry[]>([])
  const isExecuting = ref(false)
  const darkMode = ref(true)

  function setCode(value: string) {
    code.value = value
  }

  function setLanguage(lang: ProgrammingLanguage) {
    language.value = lang
  }

  function addEntry(entry: TerminalEntry) {
    history.value.unshift(entry)
  }

  function clearHistory() {
    history.value = []
  }

  function toggleDarkMode() {
    darkMode.value = !darkMode.value
  }

  function setExecuting(value: boolean) {
    isExecuting.value = value
  }

  return {
    code,
    language,
    history,
    isExecuting,
    darkMode,
    setCode,
    setLanguage,
    addEntry,
    clearHistory,
    toggleDarkMode,
    setExecuting,
  }
})
