import { useState } from 'react'
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom'
import { api } from '../api/client'
import { SearchBar } from '../components/SearchBar'
import { useApi } from '../hooks/useApi'
import { useTheme } from '../theme/useTheme'

/** Partner-portal style shell: top bar (brand · search · theme), category sidebar, content area. */
export function AppShell() {
  const { theme, toggle } = useTheme()
  const [menuOpen, setMenuOpen] = useState(false)
  const location = useLocation()
  const categories = useApi((signal) => api.categories(signal), [])

  // Close the mobile menu whenever the page changes.
  const [lastPath, setLastPath] = useState(location.pathname)
  if (lastPath !== location.pathname) {
    setLastPath(location.pathname)
    setMenuOpen(false)
  }

  return (
    <>
      <a href="#main" className="visually-hidden-focusable position-absolute p-2">
        Skip to content
      </a>
      <header className="topbar d-flex align-items-center gap-3 px-3">
        <button
          type="button"
          className="btn btn-link text-body d-lg-none p-1"
          aria-label="Categories menu"
          aria-expanded={menuOpen}
          aria-controls="sidebar"
          onClick={() => setMenuOpen((o) => !o)}
        >
          <i className="bi bi-list fs-4" aria-hidden="true" />
        </button>
        <Link to="/" className="d-flex align-items-center gap-2 text-decoration-none text-body fw-semibold">
          <span className="brand-badge" aria-hidden="true">
            <i className="bi bi-bag" />
          </span>
          <span className="d-none d-md-inline">Catalogue</span>
        </Link>
        <div className="flex-grow-1 d-flex justify-content-center">
          <SearchBar />
        </div>
        <button
          type="button"
          className="btn btn-link text-body p-1"
          onClick={toggle}
          aria-label={theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'}
        >
          <i className={`bi ${theme === 'dark' ? 'bi-sun' : 'bi-moon-stars'} fs-5`} aria-hidden="true" />
        </button>
      </header>

      <div className="d-flex">
        <nav id="sidebar" className={`sidebar p-3 ${menuOpen ? 'open' : ''}`} aria-label="Categories">
          <p className="menu-category mb-2 px-2">Categories</p>
          <ul className="list-unstyled d-grid gap-1 mb-0">
            <li>
              <NavLink to="/" end className="menu-link">
                <i className="bi bi-house" aria-hidden="true" />
                <span>Home</span>
              </NavLink>
            </li>
            {categories.data?.map((category) => (
              <li key={category.slug}>
                <NavLink to={`/categories/${category.slug}`} className="menu-link">
                  <i className="bi bi-tag" aria-hidden="true" />
                  <span>{category.name}</span>
                </NavLink>
              </li>
            ))}
          </ul>
          {categories.error && <p className="small text-danger mt-2 px-2">Categories unavailable.</p>}
        </nav>

        <main id="main" className="content flex-grow-1 p-3 p-lg-4">
          <Outlet />
        </main>
      </div>
    </>
  )
}
