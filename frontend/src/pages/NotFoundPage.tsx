import { Link } from 'react-router-dom'
import { EmptyState } from '../components/StatusViews'

export function NotFoundPage() {
  return (
    <EmptyState title="Page not found">
      <Link to="/">Back to the catalogue</Link>
    </EmptyState>
  )
}
