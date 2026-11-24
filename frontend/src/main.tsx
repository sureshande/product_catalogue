import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import 'bootstrap-icons/font/bootstrap-icons.css'
import { App } from './App'
import { routerFuture } from './routerFuture'
import './theme/app.scss'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter future={routerFuture}>
      <App />
    </BrowserRouter>
  </StrictMode>,
)
