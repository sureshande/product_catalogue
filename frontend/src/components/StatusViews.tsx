import type { ApiError } from '../api/client'

export function LoadingGrid({ count = 4 }: { count?: number }) {
  return (
    <div className="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3" role="status" aria-label="Loading products">
      {Array.from({ length: count }, (_, i) => (
        <div key={i} className="col">
          <div className="card h-100 placeholder-glow" aria-hidden="true">
            <div className="placeholder w-100" style={{ height: '11rem' }} />
            <div className="card-body">
              <span className="placeholder col-4 mb-2" />
              <span className="placeholder col-10" />
              <span className="placeholder col-6" />
            </div>
          </div>
        </div>
      ))}
    </div>
  )
}

export function ErrorState({ error, onRetry }: { error: ApiError; onRetry?: () => void }) {
  return (
    <div className="alert alert-danger d-flex align-items-center gap-3" role="alert">
      <i className="bi bi-exclamation-triangle fs-4" aria-hidden="true" />
      <div className="flex-grow-1">{error.message}</div>
      {onRetry && (
        <button type="button" className="btn btn-sm btn-outline-danger" onClick={onRetry}>
          Try again
        </button>
      )}
    </div>
  )
}

export function EmptyState({ title, children }: { title: string; children?: React.ReactNode }) {
  return (
    <div className="card">
      <div className="card-body text-center py-5">
        <i className="bi bi-search display-6 text-body-secondary" aria-hidden="true" />
        <h2 className="h5 mt-3">{title}</h2>
        {children && <p className="text-body-secondary mb-0">{children}</p>}
      </div>
    </div>
  )
}
