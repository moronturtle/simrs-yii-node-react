import type { NextFunction, Request, Response } from 'express'
import { HttpError } from '../http.js'

export function notFound(_req: Request, res: Response) {
  res.status(404).json({
    success: false,
    data: null,
    error: { code: 'NOT_FOUND', message: 'Endpoint tidak ditemukan' },
  })
}

export function errorHandler(
  error: Error,
  _req: Request,
  res: Response,
  _next: NextFunction,
) {
  if (error instanceof HttpError) {
    return res.status(error.status).json({
      success: false,
      data: null,
      error: { code: error.code, message: error.message },
    })
  }

  console.error(error)

  return res.status(500).json({
    success: false,
    data: null,
    error: { code: 'INTERNAL_ERROR', message: 'Terjadi kesalahan pada service' },
  })
}
