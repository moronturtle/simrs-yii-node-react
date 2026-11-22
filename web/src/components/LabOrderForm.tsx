import { useState, type FormEvent } from 'react'

const tests = [
  { name: 'Darah Lengkap', price: 75000 },
  { name: 'Urine Lengkap', price: 50000 },
  { name: 'Rontgen Thorax', price: 150000 },
]

type Props = {
  onSubmit: (body: { test_name: string; price: number }) => Promise<void>
}

export function LabOrderForm({ onSubmit }: Props) {
  const [testName, setTestName] = useState(tests[0].name)
  const [price, setPrice] = useState(String(tests[0].price))
  const [saving, setSaving] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)

    try {
      await onSubmit({ test_name: testName, price: Number(price) })
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="master-form" onSubmit={handleSubmit}>
      <h2>Order Laboratorium</h2>
      <label>
        Pemeriksaan
        <select
          onChange={(event) => {
            const test = tests.find((item) => item.name === event.target.value)
            setTestName(event.target.value)
            setPrice(String(test?.price ?? 0))
          }}
          value={testName}
        >
          {tests.map((test) => (
            <option key={test.name} value={test.name}>
              {test.name} · Rp{test.price.toLocaleString('id-ID')}
            </option>
          ))}
        </select>
      </label>
      <label>
        Biaya
        <input
          min="0"
          onChange={(event) => setPrice(event.target.value)}
          required
          type="number"
          value={price}
        />
      </label>
      <button disabled={saving} type="submit">
        {saving ? 'Mengirim...' : 'Kirim Order Lab'}
      </button>
    </form>
  )
}
