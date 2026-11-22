import type { NextFunction, Request, Response } from 'express'

export class HttpError extends Error {
  constructor(
    public status: number,
    public code: string,
    message: string,
  ) {
    super(message)
  }
}

export function success(res: Response, data: unknown, status = 200) {
  return res.status(status).json({ success: true, data, error: null })
}

export function failure(res: Response, status: number, code: string, message: string) {
  return res.status(status).json({
    success: false,
    data: null,
    error: { code, message },
  })
}

export function asyncRoute(
  handler: (req: Request, res: Response) => Promise<unknown>,
) {
  return (req: Request, res: Response, next: NextFunction) => {
    Promise.resolve(handler(req, res)).catch(next)
  }
}
