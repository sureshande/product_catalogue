import { useState } from 'react'
import { useParams } from 'react-router-dom'
import { api } from '../api/client'
import { EnquiryPanel } from '../components/EnquiryPanel'
import { formatPrice } from '../components/format'
import { RatingStars } from '../components/RatingStars'
import { ErrorState } from '../components/StatusViews'
import { useApi } from '../hooks/useApi'
import { PageHeader } from '../layout/PageHeader'

export function ProductPage() {
  const id = Number(useParams().id)
  const { data: product, error, loading, retry } = useApi((signal) => api.product(id, signal), [id])
  const [enquiring, setEnquiring] = useState(false)

  if (loading) {
    return (
      <div className="placeholder-glow" role="status" aria-label="Loading product">
        <span className="placeholder col-6 mb-4" style={{ height: '2rem' }} />
        <div className="placeholder w-100" style={{ height: '20rem' }} />
      </div>
    )
  }

  if (error || !product) {
    return error ? <ErrorState error={error} onRetry={error.status === 404 ? undefined : retry} /> : null
  }

  return (
    <>
      <PageHeader
        title={product.title}
        crumbs={[{ label: product.category.name, to: `/categories/${product.category.slug}` }, { label: 'Details' }]}
      />
      <div className="card">
        <div className="card-body p-4">
          <div className="row g-4 align-items-start">
            <div className="col-md-5">
              <img src={product.image_url} alt={product.title} className="img-fluid w-100 rounded p-3 product-detail-image" />
            </div>
            <div className="col-md-7 d-flex flex-column gap-3">
              <span className="badge text-bg-light align-self-start">{product.category.name}</span>
              <RatingStars rate={product.rating.rate} count={product.rating.count} />
              <p className="display-6 fw-semibold text-primary mb-0">{formatPrice(product.price)}</p>
              <p className="mb-0" style={{ whiteSpace: 'pre-line' }}>
                {product.description}
              </p>
              <div>
                <button type="button" className="btn btn-primary btn-lg px-4" onClick={() => setEnquiring(true)}>
                  <i className="bi bi-envelope me-2" aria-hidden="true" />
                  Enquire
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <EnquiryPanel product={product} open={enquiring} onClose={() => setEnquiring(false)} />
    </>
  )
}
