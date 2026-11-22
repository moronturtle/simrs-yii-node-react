import type { PoolClient, QueryResultRow } from 'pg'
import { pool } from '../db.js'
import { HttpError } from '../http.js'

const orderSelect = `
  SELECT
    lo.id, lo.visit_id, lo.order_number, lo.test_name, lo.status, lo.price,
    lo.requested_by, lo.requested_at, lo.created_at, lo.updated_at,
    v.visit_number, v.status AS visit_status,
    p.id AS patient_id, p.medical_record_no, p.name AS patient_name,
    requester.name AS requested_by_name,
    lr.id AS result_id, lr.result_text, lr.result_values, lr.resulted_at,
    result_user.name AS resulted_by_name
  FROM lab_orders lo
  JOIN visits v ON v.id = lo.visit_id
  JOIN patients p ON p.id = v.patient_id
  JOIN users requester ON requester.id = lo.requested_by
  LEFT JOIN lab_results lr ON lr.lab_order_id = lo.id
  LEFT JOIN users result_user ON result_user.id = lr.resulted_by
`

export type LabOrderRow = QueryResultRow & {
  id: number
  visit_id: number
  order_number: string
  test_name: string
  status: string
  price: string
  requested_by: number
  requested_at: string
  visit_number: string
  visit_status: string
  patient_id: number
  medical_record_no: string
  patient_name: string
  requested_by_name: string
  result_id?: number
  result_text?: string
  result_values?: unknown
  resulted_at?: string
  resulted_by_name?: string
}

export function serializeOrder(row: LabOrderRow) {
  return {
    id: Number(row.id),
    visit_id: Number(row.visit_id),
    order_number: row.order_number,
    test_name: row.test_name,
    status: row.status,
    price: row.price,
    requested_by: Number(row.requested_by),
    requested_at: row.requested_at,
    created_at: row.created_at,
    updated_at: row.updated_at,
    visit: {
      id: Number(row.visit_id),
      visit_number: row.visit_number,
      status: row.visit_status,
      patient: {
        id: Number(row.patient_id),
        medical_record_no: row.medical_record_no,
        name: row.patient_name,
      },
    },
    requested_by_user: { id: Number(row.requested_by), name: row.requested_by_name },
    result: row.result_id ? {
      id: Number(row.result_id),
      result_text: row.result_text,
      result_values: row.result_values,
      resulted_at: row.resulted_at,
      resulted_by_name: row.resulted_by_name,
    } : null,
  }
}

export async function listOrders(filters: { status?: string; visitId?: number }) {
  const conditions: string[] = []
  const params: (string | number)[] = []

  if (filters.status) {
    params.push(filters.status)
    conditions.push(`lo.status = $${params.length}`)
  }

  if (filters.visitId) {
    params.push(filters.visitId)
    conditions.push(`lo.visit_id = $${params.length}`)
  }

  const where = conditions.length ? `WHERE ${conditions.join(' AND ')}` : ''
  const result = await pool.query(`${orderSelect} ${where} ORDER BY lo.id DESC LIMIT 100`, params)
  return result.rows as LabOrderRow[]
}

export async function getOrder(id: number, client?: PoolClient) {
  const db = client ?? pool
  const result = await db.query(`${orderSelect} WHERE lo.id = $1`, [id])
  return (result.rows[0] as LabOrderRow | undefined) ?? null
}

export async function startOrder(id: number) {
  const result = await pool.query(
    `UPDATE lab_orders
     SET status = 'IN_PROGRESS', updated_at = CURRENT_TIMESTAMP
     WHERE id = $1 AND status = 'REQUESTED'
     RETURNING id`,
    [id],
  )

  if (result.rowCount === 0) {
    const order = await getOrder(id)
    if (!order) throw new HttpError(404, 'NOT_FOUND', 'Order lab tidak ditemukan')
    throw new HttpError(409, 'LAB_STATUS_ERROR', 'Order lab sudah diproses')
  }

  return getOrder(id)
}

export async function saveResult(
  id: number,
  data: { result_text: string; result_values?: Record<string, unknown> },
  userId: number,
) {
  const client = await pool.connect()

  try {
    await client.query('BEGIN')

    const orderResult = await client.query(
      'SELECT id, status FROM lab_orders WHERE id = $1 FOR UPDATE',
      [id],
    )
    const order = orderResult.rows[0] as { id: number; status: string } | undefined

    if (!order) throw new HttpError(404, 'NOT_FOUND', 'Order lab tidak ditemukan')
    if (order.status === 'FAILED') {
      throw new HttpError(409, 'LAB_STATUS_ERROR', 'Order lab gagal dan tidak bisa diisi hasil')
    }

    await client.query(
      `INSERT INTO lab_results
        (lab_order_id, result_text, result_values, resulted_by, resulted_at)
       VALUES ($1, $2, $3, $4, CURRENT_TIMESTAMP)
       ON CONFLICT (lab_order_id)
       DO UPDATE SET
         result_text = EXCLUDED.result_text,
         result_values = EXCLUDED.result_values,
         resulted_by = EXCLUDED.resulted_by,
         resulted_at = CURRENT_TIMESTAMP,
         updated_at = CURRENT_TIMESTAMP`,
      [id, data.result_text, JSON.stringify(data.result_values ?? null), userId],
    )

    await client.query(
      `UPDATE lab_orders
       SET status = 'COMPLETED', updated_at = CURRENT_TIMESTAMP
       WHERE id = $1`,
      [id],
    )

    await client.query('COMMIT')
    return getOrder(id, client)
  } catch (error) {
    await client.query('ROLLBACK')
    throw error
  } finally {
    client.release()
  }
}
