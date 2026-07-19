import { describe, it, expect } from 'vitest'
import { useLanguageDetection } from '@/features/code-terminal/composables/useLanguageDetection'

describe('useLanguageDetection', () => {
  const { detect } = useLanguageDetection()

  it('returns javascript for empty code', () => {
    expect(detect('')).toBe('javascript')
    expect(detect('   ')).toBe('javascript')
  })

  it('detects PHP code', () => {
    expect(detect('<?php echo "hello";')).toBe('php')
    expect(detect('$name = "test";')).toBe('php')
    expect(detect('$user->find(1)')).toBe('php')
  })

  it('detects Laravel code', () => {
    expect(detect('<?php\nuse App\\Models\\User;\nRoute::get("/test", [Controller::class, "index"]);')).toBe('laravel')
    expect(detect('$app->make(Route::class)')).toBe('laravel')
    expect(detect('<?php Illuminate\\Support\\Collection')).toBe('laravel')
  })

  it('detects SQL code', () => {
    expect(detect('SELECT * FROM users')).toBe('sql')
    expect(detect('INSERT INTO users (name) VALUES ("test")')).toBe('sql')
    expect(detect('CREATE TABLE test (id INT)')).toBe('sql')
    expect(detect('UPDATE users SET name = "test"')).toBe('sql')
    expect(detect('DELETE FROM users WHERE id = 1')).toBe('sql')
  })

  it('detects HTML code', () => {
    expect(detect('<!DOCTYPE html>')).toBe('html')
    expect(detect('<html><head></head></html>')).toBe('html')
    expect(detect('<div class="container">')).toBe('html')
  })

  it('detects CSS code', () => {
    expect(detect('.container { color: red; }')).toBe('css')
    expect(detect('body { margin: 0; }')).toBe('css')
    expect(detect('h1 { font-size: 24px; }')).toBe('css')
  })

  it('detects Lua code', () => {
    expect(detect('local x = 10')).toBe('lua')
    expect(detect('if x > 0 then print("positive") end')).toBe('lua')
    expect(detect('for i = 1, 10 do print(i) end')).toBe('lua')
    expect(detect('while true do break end')).toBe('lua')
  })

  it('detects Bash code', () => {
    expect(detect('#!/bin/bash\necho "hello"')).toBe('bash')
    expect(detect('echo "hello"')).toBe('bash')
    expect(detect('fi')).toBe('bash')
    expect(detect('done')).toBe('bash')
  })

  it('detects JavaScript as default', () => {
    expect(detect('console.log("hello")')).toBe('javascript')
    expect(detect('const x = 10')).toBe('javascript')
  })

  it('detects JavaScript function without Lua indicators', () => {
    expect(detect('var add = function(a, b) { return a + b; }')).toBe('javascript')
  })

  it('prefers Laravel over PHP when Laravel patterns present', () => {
    expect(detect('<?php\nuse App\\Http\\Controllers\\Controller;\nRoute::get("/test", [Controller::class, "index"]);')).toBe('laravel')
  })
})
