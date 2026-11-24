import { useEffect, useRef, useState, type FormEvent } from 'react'
import { api, ApiError } from '../api/client'
import type { Product } from '../api/types'

interface Props {
  product: Product
  open: boolean
  onClose: () => void
}

type Fields = 'name' | 'email' | 'phone' | 'message'
const EMPTY = { name: '', email: '', phone: '', message: '', website: '' }

/** Enquiry form in a right-hand offcanvas panel. */
export function EnquiryPanel({ product, open, onClose }: Props) {
  const [form, setForm] = useState(EMPTY)
  const [errors, setErrors] = useState<Partial<Record<Fields, string>>>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [thankYou, setThankYou] = useState<string | null>(null)
  const firstField = useRef<HTMLInputElement>(null)
  // Held in a ref so a new onClose function from the parent never re-runs the reset effect below.
  const onCloseRef = useRef(onClose)
  onCloseRef.current = onClose

  // Reset when (re)opened, focus the first field, close on Escape.
  useEffect(() => {
    if (!open) return
    setForm(EMPTY)
    setErrors({})
    setFormError(null)
    setThankYou(null)
    firstField.current?.focus()

    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onCloseRef.current()
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open])

  const update = (field: keyof typeof EMPTY) => (e: { target: { value: string } }) =>
    setForm((f) => ({ ...f, [field]: e.target.value }))

  async function submit(e: FormEvent) {
    e.preventDefault()
    setSubmitting(true)
    setErrors({})
    setFormError(null)
    try {
      const result = await api.createEnquiry({
        product_id: product.id,
        name: form.name,
        email: form.email,
        phone: form.phone || undefined,
        message: form.message || undefined,
        website: form.website || undefined,
      })
      setThankYou(result.message)
    } catch (error) {
      const apiError = error instanceof ApiError ? error : new ApiError('Something went wrong.', 0)
      const fieldErrors = Object.fromEntries(
        Object.entries(apiError.fieldErrors).map(([field, messages]) => [field, messages[0]]),
      )
      setErrors(fieldErrors)
      // Errors that don't belong to a visible field are shown above the form.
      const visible = ['name', 'email', 'phone', 'message']
      if (!Object.keys(fieldErrors).some((f) => visible.includes(f))) setFormError(apiError.message)
    } finally {
      setSubmitting(false)
    }
  }

  const field = (name: Fields, label: string, props: React.ComponentProps<'input'> = {}) => (
    <div className="mb-3">
      <label htmlFor={`enquiry-${name}`} className="form-label">
        {label}
      </label>
      <input
        id={`enquiry-${name}`}
        className={`form-control ${errors[name] ? 'is-invalid' : ''}`}
        value={form[name]}
        onChange={update(name)}
        aria-invalid={errors[name] ? true : undefined}
        aria-describedby={errors[name] ? `enquiry-${name}-error` : undefined}
        {...props}
      />
      {errors[name] && (
        <div id={`enquiry-${name}-error`} className="invalid-feedback">
          {errors[name]}
        </div>
      )}
    </div>
  )

  return (
    <>
      <div
        className={`offcanvas offcanvas-end ${open ? 'show' : ''}`}
        style={{ visibility: open ? 'visible' : 'hidden' }}
        tabIndex={-1}
        role="dialog"
        aria-modal="true"
        aria-labelledby="enquiry-title"
        aria-hidden={!open}
      >
        <div className="offcanvas-header border-bottom">
          <h2 id="enquiry-title" className="offcanvas-title h5">
            Enquire about this product
          </h2>
          <button type="button" className="btn-close" aria-label="Close" onClick={onClose} />
        </div>
        <div className="offcanvas-body">
          <p className="small text-body-secondary">{product.title}</p>

          {thankYou ? (
            <div className="text-center py-4" role="status">
              <i className="bi bi-check-circle-fill display-5 text-success" aria-hidden="true" />
              <h3 className="h4 mt-3">Thank you</h3>
              <p className="text-body-secondary">{thankYou}</p>
              <button type="button" className="btn btn-outline-secondary" onClick={onClose}>
                Continue browsing
              </button>
            </div>
          ) : (
            <form onSubmit={submit} noValidate>
              {formError && (
                <div className="alert alert-danger" role="alert">
                  {formError}
                </div>
              )}
              {field('name', 'Your name', { required: true, maxLength: 100, autoComplete: 'name', ref: firstField })}
              {field('email', 'Email', { type: 'email', required: true, maxLength: 255, autoComplete: 'email' })}
              {field('phone', 'Phone (optional)', { type: 'tel', maxLength: 30, autoComplete: 'tel' })}
              <div className="mb-3">
                <label htmlFor="enquiry-message" className="form-label">
                  Message (optional)
                </label>
                <textarea
                  id="enquiry-message"
                  className={`form-control ${errors.message ? 'is-invalid' : ''}`}
                  rows={4}
                  maxLength={2000}
                  value={form.message}
                  onChange={update('message')}
                />
                {errors.message && <div className="invalid-feedback">{errors.message}</div>}
              </div>
              <div className="hp-field" aria-hidden="true">
                <label htmlFor="enquiry-website">Website</label>
                <input id="enquiry-website" tabIndex={-1} autoComplete="off" value={form.website} onChange={update('website')} />
              </div>
              <button type="submit" className="btn btn-primary w-100" disabled={submitting}>
                {submitting ? 'Sending…' : 'Send enquiry'}
              </button>
            </form>
          )}
        </div>
      </div>
      {open && <div className="offcanvas-backdrop fade show" onClick={onClose} />}
    </>
  )
}
