import type {
  Category,
  CategoryProducts,
  EnquiryAccepted,
  EnquiryInput,
  Paginated,
  Product,
} from './types'

// The only place in the client that knows where the backend lives.
const BASE = import.meta.env.VITE_API_URL ?? '/api'

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly fieldErrors: Record<string, string[]> = {},
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  let response: Response
  try {
    response = await fetch(new URL(BASE + path, window.location.origin), {
      ...init,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...init.headers },
    })
  } catch (error) {
    if (error instanceof DOMException && error.name === 'AbortError') throw error
    throw new ApiError('We could not reach the server. Check your connection and try again.', 0)
  }

  const body = await response.json().catch(() => null)

  if (!response.ok) {
    const message =
      response.status === 429
        ? 'Too many requests. Please wait a minute and try again.'
        : (body?.message ?? 'Something went wrong. Please try again.')
    throw new ApiError(message, response.status, body?.errors ?? {})
  }

  return body as T
}

const query = (params: Record<string, string | number | undefined>) => {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== '') search.set(key, String(value))
  })
  const text = search.toString()
  return text ? `?${text}` : ''
}

export const api = {
  featured: (signal?: AbortSignal) =>
    request<{ data: Product[] }>('/products/featured', { signal }).then((r) => r.data),

  categories: (signal?: AbortSignal) =>
    request<{ data: Category[] }>('/categories', { signal }).then((r) => r.data),

  categoryProducts: (slug: string, page = 1, signal?: AbortSignal) =>
    request<CategoryProducts>(`/categories/${encodeURIComponent(slug)}/products${query({ page })}`, { signal }),

  search: (term: string, page = 1, signal?: AbortSignal) =>
    request<Paginated<Product>>(`/products${query({ search: term, page })}`, { signal }),

  product: (id: number, signal?: AbortSignal) =>
    request<{ data: Product }>(`/products/${id}`, { signal }).then((r) => r.data),

  createEnquiry: (input: EnquiryInput) =>
    request<EnquiryAccepted>('/enquiries', { method: 'POST', body: JSON.stringify(input) }),
}
