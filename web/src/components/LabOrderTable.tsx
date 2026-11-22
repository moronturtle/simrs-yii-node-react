import type { LabOrder } from '../api/lab'

type Props = {
  orders: LabOrder[]
}

export function LabOrderTable({ orders }: Props) {
  return (
    <div className="table-wrap">
      <table>
        <thead>
          <tr>
            <th>No. Order</th>
            <th>Pemeriksaan</th>
            <th>Status</th>
            <th>Hasil</th>
          </tr>
        </thead>
        <tbody>
          {orders.map((order) => (
            <tr key={order.id}>
              <td>{order.order_number}</td>
              <td>{order.test_name}</td>
              <td><span className={`status status-${order.status.toLowerCase()}`}>{order.status}</span></td>
              <td>{order.result?.result_text ?? '-'}</td>
            </tr>
          ))}
          {orders.length === 0 && <tr><td colSpan={4}>Belum ada order lab.</td></tr>}
        </tbody>
      </table>
    </div>
  )
}
