import { http, HttpResponse } from 'msw'
import { setupServer } from 'msw/node'
import { categories, featured, jacket, makeProduct } from './fixtures'

const page = <T,>(data: T[]) => ({ data, meta: { current_page: 1, per_page: 12, total: data.length, last_page: 1 } })

/** Default happy-path backend. Individual tests override handlers with server.use(...). */
export const handlers = [
  http.get('*/api/categories', () => HttpResponse.json({ data: categories })),

  http.get('*/api/products/featured', () => HttpResponse.json({ data: featured })),

  http.get('*/api/categories/:slug/products', ({ params }) =>
    HttpResponse.json({
      category: categories.find((c) => c.slug === params.slug) ?? categories[0],
      top_rated: [makeProduct({ id: 12, title: 'WD 4TB Gaming Drive' }), makeProduct({ id: 11, title: 'Silicon Power SSD' })],
      products: page([makeProduct({ id: 10, title: 'SanDisk SSD PLUS' }), makeProduct({ id: 13, title: 'Acer Monitor' })]),
    }),
  ),

  http.get('*/api/products', ({ request }) => {
    const term = new URL(request.url).searchParams.get('search')?.toLowerCase() ?? ''
    return HttpResponse.json(page(featured.filter((p) => p.title.toLowerCase().includes(term))))
  }),

  http.get('*/api/products/:id', ({ params }) =>
    Number(params.id) === jacket.id
      ? HttpResponse.json({ data: jacket })
      : HttpResponse.json({ message: `Product ${params.id} was not found.` }, { status: 404 }),
  ),

  http.post('*/api/enquiries', () =>
    HttpResponse.json({ id: 1, message: 'Thank you! Our sales team will be in touch soon.' }, { status: 202 }),
  ),
]

export const server = setupServer(...handlers)
