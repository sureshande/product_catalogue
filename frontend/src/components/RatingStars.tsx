interface Props {
  rate: number
  count?: number
}

export function RatingStars({ rate, count }: Props) {
  const label = `Rated ${rate.toFixed(1)} out of 5${count !== undefined ? ` from ${count} reviews` : ''}`

  return (
    <span className="d-inline-flex align-items-center gap-1 small" aria-label={label} title={label}>
      <span className="rating-stars" aria-hidden="true">
        {[1, 2, 3, 4, 5].map((star) => (
          <i
            key={star}
            className={`bi ${rate >= star ? 'bi-star-fill' : rate >= star - 0.5 ? 'bi-star-half' : 'bi-star'}`}
          />
        ))}
      </span>
      <span className="text-body-secondary" aria-hidden="true">
        {rate.toFixed(1)}
        {count !== undefined && ` (${count})`}
      </span>
    </span>
  )
}
