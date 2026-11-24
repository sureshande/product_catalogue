import { api } from '../api/client'
import { ProductGrid } from '../components/ProductGrid'
import { EmptyState, ErrorState, LoadingGrid } from '../components/StatusViews'
import { useApi } from '../hooks/useApi'
import { PageHeader } from '../layout/PageHeader'

export function HomePage() {
  const { data, error, loading, retry } = useApi((signal) => api.featured(signal), [])

  return (
    <>
      <PageHeader title="Top rated" />
      {loading && <LoadingGrid count={5} />}
      {error && <ErrorState error={error} onRetry={retry} />}
      {data && data.length === 0 && <EmptyState title="No featured products yet" />}
      {data && data.length > 0 && <ProductGrid products={data} columns={5} label="Top rated products" />}
    </>
  )
}
