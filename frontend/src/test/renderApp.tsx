import { render } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { App } from '../App'
import { routerFuture } from '../routerFuture'

/** Renders the whole app (shell + routes) at a URL, like a user opening that page. */
export function renderApp(path = '/') {
  const user = userEvent.setup()
  const result = render(
    <MemoryRouter initialEntries={[path]} future={routerFuture}>
      <App />
    </MemoryRouter>,
  )
  return { user, ...result }
}
