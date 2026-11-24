import { useCallback, useEffect, useState } from 'react'
import { ApiError } from '../api/client'

interface State<T> {
  data: T | null
  error: ApiError | null
  loading: boolean
}

/**
 * Runs an API call when its dependencies change, cancelling the previous call.
 * Returns data / error / loading plus a retry function.
 */
export function useApi<T>(load: (signal: AbortSignal) => Promise<T>, deps: unknown[]) {
  const [state, setState] = useState<State<T>>({ data: null, error: null, loading: true })
  const [attempt, setAttempt] = useState(0)

  useEffect(() => {
    const controller = new AbortController()
    setState((s) => ({ ...s, error: null, loading: true }))

    load(controller.signal)
      .then((data) => setState({ data, error: null, loading: false }))
      .catch((error: unknown) => {
        if (controller.signal.aborted) return
        const apiError = error instanceof ApiError ? error : new ApiError('Something went wrong.', 0)
        setState({ data: null, error: apiError, loading: false })
      })

    return () => controller.abort()
    // eslint-disable-next-line react-hooks/exhaustive-deps -- callers pass the real dependencies
  }, [...deps, attempt])

  const retry = useCallback(() => setAttempt((n) => n + 1), [])

  return { ...state, retry }
}
