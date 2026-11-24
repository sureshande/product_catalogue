import { Link } from 'react-router-dom'

export interface Crumb {
  label: string
  to?: string
}

/** Breadcrumb + title row (portal "inner-header"). */
export function PageHeader({ title, crumbs = [], children }: { title: string; crumbs?: Crumb[]; children?: React.ReactNode }) {
  return (
    <div className="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
      <div>
        <nav aria-label="Breadcrumb">
          <ol className="breadcrumb small mb-1">
            <li className="breadcrumb-item">
              <Link to="/">Catalogue</Link>
            </li>
            {crumbs.map((crumb) => (
              <li key={crumb.label} className="breadcrumb-item">
                {crumb.to ? <Link to={crumb.to}>{crumb.label}</Link> : crumb.label}
              </li>
            ))}
          </ol>
        </nav>
        <h1 className="h3 mb-0">{title}</h1>
      </div>
      {children}
    </div>
  )
}
