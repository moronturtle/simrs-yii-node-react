import { useCallback, useEffect, useMemo, useState, type FormEvent } from 'react'
import {
  createResource,
  deleteResource,
  listResource,
  updateResource,
  type MasterRow,
} from '../api/masterData'
import { useAuth } from '../auth/useAuth'
import { MasterDataForm } from '../components/MasterDataForm'
import { resources, type FieldConfig, type ResourceKey } from '../masterData/config'

const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

function getValue(row: MasterRow, path: string) {
  return path.split('.').reduce<unknown>((value, key) => {
    if (value && typeof value === 'object') {
      return (value as Record<string, unknown>)[key]
    }

    return undefined
  }, row)
}

function formatCell(row: MasterRow, name: string, type?: string) {
  const value = getValue(row, name)

  if (type === 'boolean') {
    return value ? 'Aktif' : 'Nonaktif'
  }

  if (name === 'day_of_week') {
    return dayNames[Number(value)] ?? String(value ?? '-')
  }

  if (type === 'number') {
    return Number(value ?? 0).toLocaleString('id-ID')
  }

  return String(value ?? '-')
}

function normalizePayload(fields: FieldConfig[], values: Record<string, string | boolean>) {
  return Object.fromEntries(
    fields.map((field) => {
      const value = values[field.name]

      if (field.type === 'checkbox') {
        return [field.name, Boolean(value)]
      }

      if (field.type === 'number' || field.source || field.name === 'day_of_week') {
        return [field.name, Number(value)]
      }

      return [field.name, value === '' ? null : value]
    }),
  )
}

export function MasterDataPage() {
  const { token, hasRole } = useAuth()
  const [resourceKey, setResourceKey] = useState<ResourceKey>('patients')
  const [rows, setRows] = useState<MasterRow[]>([])
  const [sources, setSources] = useState({ doctors: [] as MasterRow[], polis: [] as MasterRow[] })
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [editing, setEditing] = useState<MasterRow | null>(null)
  const [formOpen, setFormOpen] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const resource = useMemo(
    () => resources.find((item) => item.key === resourceKey) ?? resources[0],
    [resourceKey],
  )
  const canWrite = hasRole(resource.writeRoles)

  const loadRows = useCallback(async () => {
    if (!token) return

    setLoading(true)
    setError('')

    try {
      const result = await listResource(resource.endpoint, token, query)
      setRows(result.items)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memuat data')
    } finally {
      setLoading(false)
    }
  }, [query, resource.endpoint, token])

  useEffect(() => {
    void Promise.resolve().then(loadRows)
  }, [loadRows])

  useEffect(() => {
    if (!token) return

    Promise.all([
      listResource('/doctors', token),
      listResource('/polis', token),
    ]).then(([doctors, polis]) => {
      setSources({ doctors: doctors.items, polis: polis.items })
    }).catch(() => undefined)
  }, [token])

  function handleSearch(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setQuery(search)
  }

  async function handleSubmit(values: Record<string, string | boolean>) {
    if (!token) return

    const payload = normalizePayload(resource.fields, values)

    if (editing) {
      await updateResource(resource.endpoint, token, editing.id, payload)
    } else {
      await createResource(resource.endpoint, token, payload)
    }

    setEditing(null)
    setFormOpen(false)
    await loadRows()
  }

  async function handleDelete(row: MasterRow) {
    if (!token || !window.confirm(`Hapus ${resource.title} ini?`)) return

    setError('')
    try {
      await deleteResource(resource.endpoint, token, row.id)
      await loadRows()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal menghapus data')
    }
  }

  return (
    <section>
      <p className="eyebrow">Master Data</p>
      <h1>Data referensi SIMRS</h1>
      <p className="muted">Kelola pasien, dokter, poli, jadwal, tindakan, dan obat.</p>

      <div className="tabs">
        {resources.map((item) => (
          <button
            className={item.key === resourceKey ? 'tab active' : 'tab'}
            key={item.key}
            onClick={() => {
              setResourceKey(item.key)
              setEditing(null)
              setFormOpen(false)
            }}
            type="button"
          >
            {item.title}
          </button>
        ))}
      </div>

      <div className="toolbar">
        <form onSubmit={handleSearch}>
          <input
            onChange={(event) => setSearch(event.target.value)}
            placeholder={`Cari ${resource.title.toLowerCase()}...`}
            value={search}
          />
          <button className="secondary" type="submit">Cari</button>
        </form>

        {canWrite && (
          <button onClick={() => { setEditing(null); setFormOpen(true) }} type="button">
            Tambah {resource.title}
          </button>
        )}
      </div>

      {error && <p className="error">{error}</p>}
      {loading && <p className="muted">Memuat data...</p>}

      {formOpen && canWrite && (
        <MasterDataForm
          key={`${resource.key}-${editing?.id ?? 'new'}`}
          onCancel={() => { setEditing(null); setFormOpen(false) }}
          onSubmit={handleSubmit}
          resource={resource}
          row={editing}
          sources={sources}
        />
      )}

      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              {resource.columns.map((column) => <th key={column.name}>{column.label}</th>)}
              {canWrite && <th>Aksi</th>}
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={row.id}>
                {resource.columns.map((column) => (
                  <td key={column.name}>{formatCell(row, column.name, column.type)}</td>
                ))}
                {canWrite && (
                  <td className="row-actions">
                    <button onClick={() => { setEditing(row); setFormOpen(true) }} type="button">Edit</button>
                    <button className="danger" onClick={() => void handleDelete(row)} type="button">Hapus</button>
                  </td>
                )}
              </tr>
            ))}
            {!loading && rows.length === 0 && (
              <tr><td colSpan={resource.columns.length + 1}>Data belum tersedia.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </section>
  )
}
