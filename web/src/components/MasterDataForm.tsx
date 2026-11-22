import { useState, type FormEvent } from 'react'
import type { MasterRow } from '../api/masterData'
import type { FieldConfig, ResourceConfig } from '../masterData/config'

type FormValues = Record<string, string | boolean>

type OptionSources = {
  doctors: MasterRow[]
  polis: MasterRow[]
}

type Props = {
  resource: ResourceConfig
  row: MasterRow | null
  sources: OptionSources
  onSubmit: (values: FormValues) => Promise<void>
  onCancel: () => void
}

function initialValues(resource: ResourceConfig, row: MasterRow | null): FormValues {
  return Object.fromEntries(
    resource.fields.map((field) => {
      const value = row?.[field.name]

      if (field.type === 'checkbox') {
        return [field.name, Boolean(value ?? true)]
      }

      if (field.type === 'time' && typeof value === 'string') {
        return [field.name, value.slice(0, 5)]
      }

      return [field.name, value === null || value === undefined ? '' : String(value)]
    }),
  )
}

function fieldOptions(field: FieldConfig, sources: OptionSources) {
  if (field.source === 'doctors') {
    return sources.doctors.map((doctor) => ({
      value: String(doctor.id),
      label: String(doctor.name),
    }))
  }

  if (field.source === 'polis') {
    return sources.polis.map((poli) => ({
      value: String(poli.id),
      label: String(poli.name),
    }))
  }

  return field.options ?? []
}

export function MasterDataForm({ resource, row, sources, onSubmit, onCancel }: Props) {
  const [values, setValues] = useState<FormValues>(() => initialValues(resource, row))
  const [saving, setSaving] = useState(false)

  function setValue(name: string, value: string | boolean) {
    setValues((current) => ({ ...current, [name]: value }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)

    try {
      await onSubmit(values)
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="master-form" onSubmit={handleSubmit}>
      <h2>{row ? `Ubah ${resource.title}` : `Tambah ${resource.title}`}</h2>

      <div className="form-grid">
        {resource.fields.map((field) => {
          const value = values[field.name] ?? ''

          if (field.type === 'checkbox') {
            return (
              <label className="checkbox" key={field.name}>
                <input
                  checked={Boolean(value)}
                  onChange={(event) => setValue(field.name, event.target.checked)}
                  type="checkbox"
                />
                {field.label}
              </label>
            )
          }

          if (field.type === 'textarea') {
            return (
              <label key={field.name}>
                {field.label}
                <textarea
                  onChange={(event) => setValue(field.name, event.target.value)}
                  required={field.required}
                  value={String(value)}
                />
              </label>
            )
          }

          if (field.type === 'select') {
            return (
              <label key={field.name}>
                {field.label}
                <select
                  onChange={(event) => setValue(field.name, event.target.value)}
                  required={field.required}
                  value={String(value)}
                >
                  <option value="">Pilih {field.label}</option>
                  {fieldOptions(field, sources).map((option) => (
                    <option key={option.value} value={option.value}>
                      {option.label}
                    </option>
                  ))}
                </select>
              </label>
            )
          }

          return (
            <label key={field.name}>
              {field.label}
              <input
                onChange={(event) => setValue(field.name, event.target.value)}
                required={field.required}
                type={field.type ?? 'text'}
                value={String(value)}
              />
            </label>
          )
        })}
      </div>

      <div className="actions">
        <button disabled={saving} type="submit">
          {saving ? 'Menyimpan...' : 'Simpan'}
        </button>
        <button className="secondary" onClick={onCancel} type="button">
          Batal
        </button>
      </div>
    </form>
  )
}
