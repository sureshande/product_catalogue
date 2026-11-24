import type { Product } from '../api/types'
import { ProductCard } from './ProductCard'

interface Props {
  products: Product[]
  /** Columns on extra-large screens. */
  columns?: 3 | 4 | 5
  label: string
}

export function ProductGrid({ products, columns = 4, label }: Props) {
  return (
    <ul className={`row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-${columns} g-3 list-unstyled mb-0`} aria-label={label}>
      {products.map((product) => (
        <li key={product.id} className="col">
          <ProductCard product={product} />
        </li>
      ))}
    </ul>
  )
}
