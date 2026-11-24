import { Link } from 'react-router-dom'
import type { Product } from '../api/types'
import { formatPrice } from './format'
import { RatingStars } from './RatingStars'

export function ProductCard({ product }: { product: Product }) {
  return (
    <article className="card h-100 product-card position-relative">
      <img src={product.image_url} alt="" className="product-image" loading="lazy" />
      <div className="card-body d-flex flex-column gap-2">
        <span className="badge text-bg-light align-self-start fw-medium">{product.category.name}</span>
        <h3 className="h6 product-title mb-0">
          {/* stretched-link makes the whole tile clickable while keeping one accessible link */}
          <Link to={`/products/${product.id}`} className="stretched-link text-reset text-decoration-none">
            {product.title}
          </Link>
        </h3>
        <RatingStars rate={product.rating.rate} count={product.rating.count} />
        <p className="fs-5 fw-semibold text-primary mt-auto mb-0">{formatPrice(product.price)}</p>
      </div>
    </article>
  )
}
