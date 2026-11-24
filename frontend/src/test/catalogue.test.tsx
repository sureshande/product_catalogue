import { screen, within } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { featured } from './fixtures'
import { renderApp } from './renderApp'
import { server } from './server'

describe('Home page', () => {
  it('shows the 5 featured "top rated" products in order', async () => {
    renderApp('/')

    const grid = await screen.findByRole('list', { name: 'Top rated products' })
    const titles = within(grid).getAllByRole('link').map((link) => link.textContent)

    expect(titles).toEqual(featured.map((p) => p.title))
  })

  it('lists category links in the sidebar', async () => {
    renderApp('/')

    const nav = screen.getByRole('navigation', { name: 'Categories' })
    expect(await within(nav).findByRole('link', { name: 'Electronics' })).toHaveAttribute('href', '/categories/electronics')
    expect(within(nav).getByRole('link', { name: 'Jewelery' })).toBeInTheDocument()
  })

  it('shows a friendly error with retry when the catalogue is unavailable', async () => {
    server.use(
      http.get('*/api/products/featured', () =>
        HttpResponse.json({ message: 'The product catalogue is temporarily unavailable.' }, { status: 503 }),
      ),
    )
    const { user } = renderApp('/')

    expect(await screen.findByRole('alert')).toHaveTextContent('temporarily unavailable')

    server.resetHandlers() // backend recovers
    await user.click(screen.getByRole('button', { name: 'Try again' }))

    expect(await screen.findByRole('list', { name: 'Top rated products' })).toBeInTheDocument()
  })
})

describe('Category page', () => {
  it('shows top rated products and additional products for the category', async () => {
    const { user } = renderApp('/')

    await user.click(await screen.findByRole('link', { name: 'Electronics' }))

    const topRated = await screen.findByRole('list', { name: 'Top rated in Electronics' })
    expect(within(topRated).getAllByRole('link').map((l) => l.textContent)).toEqual(['WD 4TB Gaming Drive', 'Silicon Power SSD'])

    const more = screen.getByRole('list', { name: 'More in Electronics' })
    expect(within(more).getAllByRole('link').map((l) => l.textContent)).toEqual(['SanDisk SSD PLUS', 'Acer Monitor'])
  })
})

describe('Search', () => {
  it('only shows products matching the term after clicking Search', async () => {
    let searchedFor: string | null = null
    server.use(
      http.get('*/api/products', ({ request }) => {
        searchedFor = new URL(request.url).searchParams.get('search')
        return HttpResponse.json({
          data: [featured[1]],
          meta: { current_page: 1, per_page: 12, total: 1, last_page: 1 },
        })
      }),
    )
    const { user } = renderApp('/')

    await user.type(screen.getByRole('searchbox', { name: 'Search products' }), 'jacket')
    await user.click(screen.getByRole('button', { name: 'Search' }))

    const results = await screen.findByRole('list', { name: 'Search results' })
    expect(within(results).getAllByRole('link').map((l) => l.textContent)).toEqual(['Mens Cotton Jacket'])
    expect(searchedFor).toBe('jacket')
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Results for “jacket”')
  })

  it('tells the user when nothing matches', async () => {
    renderApp('/search?q=submarine')

    expect(await screen.findByText('No products match your search')).toBeInTheDocument()
  })

  it('clears the results when the search box is emptied (e.g. the × button)', async () => {
    const { user } = renderApp('/search?q=jacket')
    expect(await screen.findByRole('heading', { level: 1 })).toHaveTextContent('Results for “jacket”')

    await user.clear(screen.getByRole('searchbox', { name: 'Search products' }))

    expect(await screen.findByRole('list', { name: 'Top rated products' })).toBeInTheDocument()
    expect(screen.queryByRole('list', { name: 'Search results' })).not.toBeInTheDocument()
  })

  it('keeps the current page when the box is emptied outside the results page', async () => {
    const { user } = renderApp('/categories/electronics')
    await screen.findByRole('list', { name: 'Top rated in Electronics' })

    await user.type(screen.getByRole('searchbox', { name: 'Search products' }), 'ssd')
    await user.clear(screen.getByRole('searchbox', { name: 'Search products' }))

    expect(screen.getByRole('list', { name: 'Top rated in Electronics' })).toBeInTheDocument()
  })

  it('disables the Search button until a term is entered', () => {
    renderApp('/')

    expect(screen.getByRole('button', { name: 'Search' })).toBeDisabled()
  })
})

describe('Product page', () => {
  it('opens the product details when a product tile is clicked', async () => {
    const { user } = renderApp('/')

    await user.click(await screen.findByRole('link', { name: 'Mens Cotton Jacket' }))

    expect(await screen.findByRole('heading', { level: 1, name: 'Mens Cotton Jacket' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Enquire' })).toBeInTheDocument()
  })

  it('shows not found for an unknown product, without a retry button', async () => {
    renderApp('/products/999')

    expect(await screen.findByRole('alert')).toHaveTextContent('Product 999 was not found.')
    expect(screen.queryByRole('button', { name: 'Try again' })).not.toBeInTheDocument()
  })
})
