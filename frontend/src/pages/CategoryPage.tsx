import { useParams, useSearchParams } from 'react-router-dom'
import { api } from '../api/client'
import { Pagination } from '../components/Pagination'
import { ProductGrid } from '../components/ProductGrid'
import { EmptyState, ErrorState, LoadingGrid } from '../components/StatusViews'
import { useApi } from '../hooks/useApi'
import { PageHeader } from '../layout/PageHeader'

export function CategoryPage() {
  const { slug = '' } = useParams()
  const [params] = useSearchParams()
  const page = Math.max(1, Number(params.get('page')) || 1)
  const { data, error, loading, retry } = useApi((signal) => api.categoryProducts(slug, page, signal), [slug, page])

  const name = data?.category.name ?? ''

  return (
    <>
      <PageHeader title={name || 'Category'} crumbs={[{ label: name || slug }]}>
        {data && <span className="text-body-secondary small">{data.top_rated.length + data.products.meta.total} products</span>}
      </PageHeader>

      {loading && <LoadingGrid />}
      {error && <ErrorState error={error} onRetry={error.status === 404 ? undefined : retry} />}

      {data && (
        <>
          <section aria-labelledby="top-rated-heading" className="mb-5">
            <h2 id="top-rated-heading" className="h5 mb-3">
              <i className="bi bi-award me-2 text-warning" aria-hidden="true" />
              Top rated in {name}
            </h2>
            {data.top_rated.length > 0 ? (
              <ProductGrid products={data.top_rated} columns={3} label={`Top rated in ${name}`} />
            ) : (
              <EmptyState title="No products in this category yet" />
            )}
          </section>

          {data.products.meta.total > 0 && (
            <section aria-labelledby="more-heading">
              <h2 id="more-heading" className="h5 mb-3">
                More in {name}
              </h2>
              <ProductGrid products={data.products.data} label={`More in ${name}`} />
              <Pagination meta={data.products.meta} />
            </section>
          )}
        </>
      )}
    </>
  )
}
