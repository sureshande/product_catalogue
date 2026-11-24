import { useEffect, useState, type FormEvent } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'

export function SearchBar() {
  const [params] = useSearchParams()
  const [term, setTerm] = useState(params.get('q') ?? '')
  const navigate = useNavigate()

  // Keep the box in sync when the URL changes (back button, links).
  useEffect(() => setTerm(params.get('q') ?? ''), [params])

  function submit(e: FormEvent) {
    e.preventDefault()
    const q = term.trim()
    if (q) navigate(`/search?q=${encodeURIComponent(q)}`)
  }

  return (
    <form role="search" onSubmit={submit} className="d-flex flex-grow-1 gap-2" style={{ maxWidth: '36rem' }}>
      <label htmlFor="search" className="visually-hidden">
        Search products
      </label>
      <div className="input-group">
        <span className="input-group-text bg-body" aria-hidden="true">
          <i className="bi bi-search" />
        </span>
        <input
          id="search"
          type="search"
          className="form-control"
          placeholder="Search products…"
          maxLength={100}
          value={term}
          onChange={(e) => setTerm(e.target.value)}
        />
      </div>
      <button type="submit" className="btn btn-primary" disabled={!term.trim()}>
        Search
      </button>
    </form>
  )
}
