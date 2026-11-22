import { useState, type FormEvent } from 'react'
import type { MasterRow } from '../api/masterData'

type ItemRow = {
  medicine_id: string
  dose: string
  frequency: string
  quantity: string
}

type Props = {
  medicines: MasterRow[]
  onSubmit: (body: {
    notes?: string
    items: { medicine_id: number; dose: string; frequency: string; quantity: number }[]
  }) => Promise<void>
}

const emptyItem: ItemRow = {
  medicine_id: '',
  dose: '',
  frequency: '',
  quantity: '1',
}

export function PrescriptionForm({ medicines, onSubmit }: Props) {
  const [items, setItems] = useState<ItemRow[]>([emptyItem])
  const [notes, setNotes] = useState('')
  const [saving, setSaving] = useState(false)

  function updateItem(index: number, name: keyof ItemRow, value: string) {
    setItems((current) => current.map((item, i) => (
      i === index ? { ...item, [name]: value } : item
    )))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)

    try {
      await onSubmit({
        notes,
        items: items.map((item) => ({
          medicine_id: Number(item.medicine_id),
          dose: item.dose,
          frequency: item.frequency,
          quantity: Number(item.quantity),
        })),
      })
      setItems([emptyItem])
      setNotes('')
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="master-form" onSubmit={handleSubmit}>
      <h2>Buat Resep</h2>

      {items.map((item, index) => (
        <div className="prescription-item" key={index}>
          <label>
            Obat
            <select
              onChange={(event) => updateItem(index, 'medicine_id', event.target.value)}
              required
              value={item.medicine_id}
            >
              <option value="">Pilih obat</option>
              {medicines.map((medicine) => (
                <option key={medicine.id} value={medicine.id}>
                  {String(medicine.name)} · stok {String(medicine.stock)} · Rp{Number(medicine.price).toLocaleString('id-ID')}
                </option>
              ))}
            </select>
          </label>

          <label>
            Dosis
            <input
              onChange={(event) => updateItem(index, 'dose', event.target.value)}
              placeholder="500mg"
              required
              value={item.dose}
            />
          </label>

          <label>
            Frekuensi
            <input
              onChange={(event) => updateItem(index, 'frequency', event.target.value)}
              placeholder="3x sehari"
              required
              value={item.frequency}
            />
          </label>

          <label>
            Qty
            <input
              min="1"
              onChange={(event) => updateItem(index, 'quantity', event.target.value)}
              required
              type="number"
              value={item.quantity}
            />
          </label>

          {items.length > 1 && (
            <button
              className="danger"
              onClick={() => setItems((current) => current.filter((_, i) => i !== index))}
              type="button"
            >
              Hapus
            </button>
          )}
        </div>
      ))}

      <label>
        Catatan resep
        <textarea onChange={(event) => setNotes(event.target.value)} value={notes} />
      </label>

      <div className="actions">
        <button className="secondary" onClick={() => setItems((current) => [...current, emptyItem])} type="button">
          Tambah Item
        </button>
        <button disabled={saving} type="submit">
          {saving ? 'Menyimpan...' : 'Simpan Resep'}
        </button>
      </div>
    </form>
  )
}
