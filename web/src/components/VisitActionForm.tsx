import { useState, type FormEvent } from 'react'
import type { MasterRow } from '../api/masterData'

type Props = {
  actions: MasterRow[]
  onSubmit: (body: { medical_action_id: number; quantity: number; notes: string }) => Promise<void>
}

export function VisitActionForm({ actions, onSubmit }: Props) {
  const [actionId, setActionId] = useState('')
  const [quantity, setQuantity] = useState('1')
  const [notes, setNotes] = useState('')
  const [saving, setSaving] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)

    try {
      await onSubmit({
        medical_action_id: Number(actionId),
        quantity: Number(quantity),
        notes,
      })
      setActionId('')
      setQuantity('1')
      setNotes('')
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="master-form" onSubmit={handleSubmit}>
      <h2>Tambah Tindakan</h2>

      <div className="form-grid">
        <label>
          Tindakan
          <select onChange={(event) => setActionId(event.target.value)} required value={actionId}>
            <option value="">Pilih tindakan</option>
            {actions.map((action) => (
              <option key={action.id} value={action.id}>
                {String(action.code)} · {String(action.name)} · Rp{Number(action.price).toLocaleString('id-ID')}
              </option>
            ))}
          </select>
        </label>

        <label>
          Jumlah
          <input
            min="1"
            onChange={(event) => setQuantity(event.target.value)}
            required
            type="number"
            value={quantity}
          />
        </label>

        <label>
          Catatan
          <input onChange={(event) => setNotes(event.target.value)} value={notes} />
        </label>
      </div>

      <div className="actions">
        <button disabled={saving} type="submit">
          {saving ? 'Menambahkan...' : 'Tambah Tindakan'}
        </button>
      </div>
    </form>
  )
}
