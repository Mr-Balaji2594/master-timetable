import { Alert } from 'react-bootstrap'
import { usePage } from '@inertiajs/react'

export default function FormErrors() {
  const errors = usePage().props.errors ?? {}
  const entries = Object.entries(errors).filter(([, v]) => v)
  if (entries.length === 0) return null

  return (
    <Alert variant="danger" dismissible className="py-2">
      <ul className="mb-0" style={{ paddingLeft: '1.2rem' }}>
        {entries.map(([field, messages]) => (
          <li key={field}>{Array.isArray(messages) ? messages[0] : messages}</li>
        ))}
      </ul>
    </Alert>
  )
}
