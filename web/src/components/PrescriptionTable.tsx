import type { Prescription } from '../api/pharmacy'

export function PrescriptionTable({ prescriptions }: { prescriptions: Prescription[] }) {
  return (
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Status</th>
            <th>Obat</th>
            <th>Catatan</th>
          </tr>
        </thead>
        <tbody>
          {prescriptions.map((prescription) => (
            <tr key={prescription.id}>
              <td><span className={`status status-${prescription.status.toLowerCase()}`}>{prescription.status}</span></td>
              <td>
                {prescription.items.map((item) => (
                  <div key={item.id}>
                    {String(item.medicine?.name ?? '-')} x{item.quantity} · {item.dose} · {item.frequency}
                  </div>
                ))}
              </td>
              <td>{prescription.notes || '-'}</td>
            </tr>
          ))}
          {prescriptions.length === 0 && <tr><td colSpan={3}>Belum ada resep.</td></tr>}
        </tbody>
      </table>
    </div>
  )
}
