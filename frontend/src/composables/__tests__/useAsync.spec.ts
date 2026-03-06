import { describe, it, expect } from 'vitest'
import { useAsync } from '../useAsync'

describe('useAsync', () => {
  it('inicia com status idle', () => {
    const { status, isIdle, data, error } = useAsync()
    expect(status.value).toBe('idle')
    expect(isIdle.value).toBe(true)
    expect(data.value).toBeNull()
    expect(error.value).toBeNull()
  })

  it('execute define pending e depois success com data', async () => {
    const { execute, status, data, isPending, isSuccess } = useAsync<number>()
    const promise = execute(() => Promise.resolve(42))
    expect(status.value).toBe('pending')
    expect(isPending.value).toBe(true)
    await promise
    expect(status.value).toBe('success')
    expect(isSuccess.value).toBe(true)
    expect(data.value).toBe(42)
  })

  it('execute define error em caso de falha', async () => {
    const { execute, status, error, isError } = useAsync()
    await execute(() => Promise.reject(new Error('falha')))
    expect(status.value).toBe('error')
    expect(isError.value).toBe(true)
    expect(error.value?.message).toBe('falha')
  })

  it('reset limpa data, error e status', async () => {
    const { execute, reset, data, error, status } = useAsync<number>()
    await execute(() => Promise.resolve(1))
    expect(data.value).toBe(1)
    reset()
    expect(data.value).toBeNull()
    expect(error.value).toBeNull()
    expect(status.value).toBe('idle')
  })
})
