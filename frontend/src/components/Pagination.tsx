import { Link, useLocation } from 'react-router-dom'
import type { PageMeta } from '../api/types'

/** Page links that keep the current query string (e.g. ?q=) and only change ?page=. */
export function Pagination({ meta }: { meta: PageMeta }) {
  const location = useLocation()
  if (meta.last_page <= 1) return null

  const href = (page: number) => {
    const params = new URLSearchParams(location.search)
    params.set('page', String(page))
    return `${location.pathname}?${params}`
  }

  const pages = Array.from({ length: meta.last_page }, (_, i) => i + 1)

  return (
    <nav aria-label="Pages" className="mt-4">
      <ul className="pagination justify-content-center mb-0">
        <li className={`page-item ${meta.current_page === 1 ? 'disabled' : ''}`}>
          <Link className="page-link" to={href(meta.current_page - 1)} aria-label="Previous page">
            <i className="bi bi-chevron-left" aria-hidden="true" />
          </Link>
        </li>
        {pages.map((page) => (
          <li key={page} className={`page-item ${page === meta.current_page ? 'active' : ''}`}>
            <Link className="page-link" to={href(page)} aria-current={page === meta.current_page ? 'page' : undefined}>
              {page}
            </Link>
          </li>
        ))}
        <li className={`page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}`}>
          <Link className="page-link" to={href(meta.current_page + 1)} aria-label="Next page">
            <i className="bi bi-chevron-right" aria-hidden="true" />
          </Link>
        </li>
      </ul>
    </nav>
  )
}
