import type { Category, Product } from '../api/types'

export const categories: Category[] = [
  { slug: 'electronics', name: 'Electronics' },
  { slug: 'jewelery', name: 'Jewelery' },
]

let nextId = 1
export function makeProduct(overrides: Partial<Product> = {}): Product {
  const id = overrides.id ?? nextId++
  return {
    id,
    title: `Product ${id}`,
    description: `Description of product ${id}`,
    price: 10 + id,
    image_url: `https://img.test/${id}.png`,
    category: categories[0],
    rating: { rate: 4.2, count: 100 },
    ...overrides,
  }
}

export const featured: Product[] = [
  makeProduct({ id: 11, title: 'Silicon Power SSD' }),
  makeProduct({ id: 3, title: 'Mens Cotton Jacket' }),
  makeProduct({ id: 5, title: 'Gold Bracelet', category: categories[1] }),
  makeProduct({ id: 18, title: 'Boat Neck Top' }),
  makeProduct({ id: 12, title: 'WD 4TB Gaming Drive' }),
]

export const jacket = featured[1]
