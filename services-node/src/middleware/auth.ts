import type { NextFunction, Request, Response } from 'express'
import jwt from 'jsonwebtoken'
import { config } from '../config.js'

type JwtUser = {
  sub: string
  username: string
  name?: string
  role: string
}

declare global {
  namespace Express {
    interface Request {
      user?: JwtUser
    }
  }
}

export function roleRequired(roles: string[]) {
  return (req: Request, res: Response, next: NextFunction) => {
    if (!req.user || !roles.includes(req.user.role)) {
      return res.status(403).json({
        success: false,
        data: null,
        error: { code: 'FORBIDDEN', message: 'Role tidak punya akses' },
      })
    }

    return next()
  }
}

export function authRequired(req: Request, res: Response, next: NextFunction) {
  const header = req.headers.authorization

  if (!header?.startsWith('Bearer ')) {
    return res.status(401).json({
      success: false,
      data: null,
      error: { code: 'UNAUTHORIZED', message: 'Token tidak tersedia' },
    })
  }

  try {
    req.user = jwt.verify(header.slice(7), config.jwtSecret, {
      issuer: 'simrs-yii',
    }) as JwtUser
    return next()
  } catch {
    return res.status(401).json({
      success: false,
      data: null,
      error: { code: 'UNAUTHORIZED', message: 'Token tidak valid' },
    })
  }
}
