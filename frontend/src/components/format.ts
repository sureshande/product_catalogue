const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })

export const formatPrice = (price: number) => currency.format(price)
