import { screen, within } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import { describe, expect, it } from 'vitest'
import { jacket } from './fixtures'
import { renderApp } from './renderApp'
import { server } from './server'

async function openEnquiryForm() {
  const app = renderApp(`/products/${jacket.id}`)
  await app.user.click(await screen.findByRole('button', { name: 'Enquire' }))
  const dialog = screen.getByRole('dialog', { name: 'Enquire about this product' })
  return { ...app, dialog }
}

describe('Enquiry', () => {
  it('submits contact details for the product and shows a thank you message', async () => {
    let sent: Record<string, unknown> | null = null
    server.use(
      http.post('*/api/enquiries', async ({ request }) => {
        sent = (await request.json()) as Record<string, unknown>
        return HttpResponse.json({ id: 7, message: 'Thank you! Our sales team will be in touch soon.' }, { status: 202 })
      }),
    )
    const { user, dialog } = await openEnquiryForm()

    expect(within(dialog).getByLabelText('Your name')).toHaveFocus()
    await user.type(within(dialog).getByLabelText('Your name'), 'Ada Lovelace')
    await user.type(within(dialog).getByLabelText('Email'), 'ada@example.com')
    await user.type(within(dialog).getByLabelText('Message (optional)'), 'Do you have it in large?')
    await user.click(within(dialog).getByRole('button', { name: 'Send enquiry' }))

    expect(await within(dialog).findByRole('heading', { name: 'Thank you' })).toBeInTheDocument()
    expect(sent).toEqual({
      product_id: jacket.id,
      name: 'Ada Lovelace',
      email: 'ada@example.com',
      message: 'Do you have it in large?',
    })
  })

  it('shows validation errors from the server next to the fields', async () => {
    server.use(
      http.post('*/api/enquiries', () =>
        HttpResponse.json(
          { message: 'The email field must be a valid email address.', errors: { email: ['The email field must be a valid email address.'] } },
          { status: 422 },
        ),
      ),
    )
    const { user, dialog } = await openEnquiryForm()

    await user.type(within(dialog).getByLabelText('Your name'), 'Ada')
    await user.type(within(dialog).getByLabelText('Email'), 'not-an-email')
    await user.click(within(dialog).getByRole('button', { name: 'Send enquiry' }))

    const email = within(dialog).getByLabelText('Email')
    expect(await within(dialog).findByText('The email field must be a valid email address.')).toBeInTheDocument()
    expect(email).toHaveAttribute('aria-invalid', 'true')
    expect(within(dialog).queryByRole('heading', { name: 'Thank you' })).not.toBeInTheDocument()
  })

  it('explains when the customer has sent too many enquiries', async () => {
    server.use(http.post('*/api/enquiries', () => HttpResponse.json({ message: 'Too Many Attempts.' }, { status: 429 })))
    const { user, dialog } = await openEnquiryForm()

    await user.type(within(dialog).getByLabelText('Your name'), 'Ada')
    await user.type(within(dialog).getByLabelText('Email'), 'ada@example.com')
    await user.click(within(dialog).getByRole('button', { name: 'Send enquiry' }))

    expect(await within(dialog).findByRole('alert')).toHaveTextContent('Too many requests')
  })

  it('closes with the Escape key', async () => {
    const { user, dialog } = await openEnquiryForm()

    await user.keyboard('{Escape}')

    expect(dialog).toHaveAttribute('aria-hidden', 'true')
  })
})
