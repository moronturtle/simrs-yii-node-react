import { useState, type FormEvent } from 'react'

type Props = {
  remaining: number
  onSubmit: (body: {
    amount: number
    method: 'CASH' | 'NON_CASH'
    idempotency_key: string
  }) => Promise<void>
}

function newKey(): string {
  return typeof crypto.randomUUID === 'function'
    ? crypto.randomUUID()
    : `pay-${Date.now()}`
}

export function PaymentForm({ remaining, onSubmit }: Props) {
  const [amount, setAmount] = useState(String(remaining))
  const [method, setMethod] = useState<'CASH' | 'NON_CASH'>('CASH')
  const [idempotencyKey, setIdempotencyKey] = useState(newKey())
  const [saving, setSaving] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)

    try {
      await onSubmit({
        amount: Number(amount),
        method,
        idempotency_key: idempotencyKey,
      })
      setIdempotencyKey(newKey())
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="master-form" onSubmit={handleSubmit}>
      <h2>Catat Pembayaran</h2>
      <label>
        Nominal
        <input
          max={remaining}
          min="1"
          onChange={(event) => setAmount(event.target.value)}
          required
          type="number"
          value={amount}
        />
      </label>
      <label>
        Metode
        <select onChange={(event) => setMethod(event.target.value as 'CASH' | 'NON_CASH')} value={method}>
          <option value="CASH">Tunai</option>
          <option value="NON_CASH">Non-tunai</option>
        </select>
      </label>
      <label>
        Idempotency key
        <input onChange={(event) => setIdempotencyKey(event.target.value)} required value={idempotencyKey} />
      </label>
      <button disabled={saving || remaining <= 0} type="submit">
        {saving ? 'Menyimpan...' : 'Simpan Pembayaran'}
      </button>
    </form>
  )
}
