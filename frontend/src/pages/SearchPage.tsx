import { useSearchParams } from 'react-router-dom'
import { api } from '../api/client'
import type { Paginated, Product } from '../api/types'
import { Pagination } from '../components/Pagination'
import { ProductGrid } from '../components/ProductGrid'
import { EmptyState, ErrorState, LoadingGrid } from '../components/StatusViews'
import { useApi } from '../hooks/useApi'
import { PageHeader } from '../layout/PageHeader'

export function SearchPage() {
  const [params] = useSearchParams()
  const term = (params.get('q') ?? '').trim()
  const page = Math.max(1, Number(params.get('page')) || 1)
  const { data, error, loading, retry } = useApi<Paginated<Product> | null>(
    (signal) => (term ? api.search(term, page, signal) : Promise.resolve(null)),
    [term, page],
  )

  if (!term) {
    return (
      <>
        <PageHeader title="Search" crumbs={[{ label: 'Search' }]} />
        <EmptyState title="What are you looking for?">Type a product name, description or category in the search bar.</EmptyState>
      </>
    )
  }

  return (
    <>
      <PageHeader title={`Results for “${term}”`} crumbs={[{ label: 'Search' }]}>
        {data && (
          <span className="text-body-secondary small" role="status">
            {data.meta.total} {data.meta.total === 1 ? 'product' : 'products'} found
          </span>
        )}
      </PageHeader>

      {loading && <LoadingGrid />}
      {error && <ErrorState error={error} onRetry={retry} />}
      {data && data.data.length === 0 && (
        <EmptyState title="No products match your search">Try a different or shorter search term.</EmptyState>
      )}
      {data && data.data.length > 0 && (
        <>
          <ProductGrid products={data.data} label="Search results" />
          <Pagination meta={data.meta} />
        </>
      )}
    </>
  )
}
