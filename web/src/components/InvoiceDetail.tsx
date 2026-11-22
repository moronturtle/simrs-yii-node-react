import { useCallback, useEffect, useState } from 'react'
import {
  getInvoice,
  getReceipt,
  payInvoice,
  type Invoice,
  type Payment,
  type Receipt,
} from '../api/billing'
import { useAuth } from '../auth/useAuth'
import { PaymentForm } from './PaymentForm'

type Props = {
  invoiceId: number
  onChanged: () => Promise<void>
}

function money(value: string | number) {
  return `Rp${Number(value).toLocaleString('id-ID')}`
}

export function InvoiceDetail({ invoiceId, onChanged }: Props) {
  const { token, hasRole } = useAuth()
  const [invoice, setInvoice] = useState<Invoice | null>(null)
  const [receipt, setReceipt] = useState<Receipt | null>(null)
  const [error, setError] = useState('')
  const canPay = hasRole(['KASIR'])

  const loadInvoice = useCallback(async () => {
    if (!token) return

    try {
      setInvoice(await getInvoice(token, invoiceId))
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memuat invoice')
    }
  }, [invoiceId, token])

  useEffect(() => {
    void Promise.resolve().then(loadInvoice)
  }, [invoiceId, loadInvoice])

  async function handlePay(body: Parameters<typeof payInvoice>[2]) {
    if (!token) return

    try {
      await payInvoice(token, invoiceId, body)
      await Promise.all([loadInvoice(), onChanged()])
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Pembayaran gagal')
      throw err
    }
  }

  async function showReceipt(payment: Payment) {
    if (!token) return

    try {
      setReceipt(await getReceipt(token, payment.id))
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memuat bukti bayar')
    }
  }

  if (!invoice) return <p className="muted">Pilih invoice untuk melihat detail.</p>

  const remaining = Number(invoice.total_amount) - Number(invoice.paid_amount)

  return (
    <div>
      <div className="card visit-summary">
        <h2>{invoice.invoice_number}</h2>
        <p><strong>Pasien:</strong> {String(invoice.visit?.patient?.name ?? '-')}</p>
        <p><strong>Status:</strong> <span className={`status status-${invoice.status.toLowerCase()}`}>{invoice.status}</span></p>
        <p><strong>Total:</strong> {money(invoice.total_amount)} · <strong>Terbayar:</strong> {money(invoice.paid_amount)} · <strong>Sisa:</strong> {money(remaining)}</p>
      </div>

      {error && <p className="error">{error}</p>}
      {canPay && remaining > 0 && <PaymentForm key={invoice.id} remaining={remaining} onSubmit={handlePay} />}

      <h2 className="section-title">Rincian Biaya</h2>
      <div className="table-wrap">
        <table>
          <thead>
            <tr><th>Jenis</th><th>Deskripsi</th><th>Qty</th><th>Harga</th><th>Total</th></tr>
          </thead>
          <tbody>
            {invoice.items.map((item) => (
              <tr key={item.id}>
                <td>{item.item_type}</td>
                <td>{item.description}</td>
                <td>{item.quantity}</td>
                <td>{money(item.unit_price)}</td>
                <td>{money(item.total_price)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <h2 className="section-title">Pembayaran</h2>
      <div className="table-wrap">
        <table>
          <thead>
            <tr><th>No. Payment</th><th>Metode</th><th>Nominal</th><th>Waktu</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            {invoice.payments.map((payment) => (
              <tr key={payment.id}>
                <td>{payment.payment_number}</td>
                <td>{payment.method}</td>
                <td>{money(payment.amount)}</td>
                <td>{payment.paid_at}</td>
                <td><button className="secondary" onClick={() => void showReceipt(payment)} type="button">Receipt</button></td>
              </tr>
            ))}
            {invoice.payments.length === 0 && <tr><td colSpan={5}>Belum ada pembayaran.</td></tr>}
          </tbody>
        </table>
      </div>

      {receipt?.payment && (
        <div className="card receipt">
          <h2>Bukti Pembayaran</h2>
          <p><strong>No:</strong> {receipt.payment.payment_number}</p>
          <p><strong>Pasien:</strong> {String(receipt.visit?.patient?.name ?? '-')}</p>
          <p><strong>Nominal:</strong> {money(receipt.payment.amount)} via {receipt.payment.method}</p>
          <p><strong>Status invoice:</strong> {receipt.invoice?.status ?? '-'}</p>
        </div>
      )}
    </div>
  )
}
