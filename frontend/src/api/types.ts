// The backend's public contract. Mirrors the Laravel API Resources — never the supplier's shape.

export interface Category {
  slug: string
  name: string
}

export interface Product {
  id: number
  title: string
  description: string
  price: number
  image_url: string
  category: Category
  rating: { rate: number; count: number }
}

export interface PageMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export interface Paginated<T> {
  data: T[]
  meta: PageMeta
}

export interface CategoryProducts {
  category: Category
  top_rated: Product[]
  products: Paginated<Product>
}

export interface EnquiryInput {
  product_id: number
  name: string
  email: string
  phone?: string
  message?: string
  website?: string // honeypot — always empty for humans
}

export interface EnquiryAccepted {
  id: number
  message: string
}
