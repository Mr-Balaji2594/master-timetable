import { Controller } from 'react-hook-form'
import { Form } from 'react-bootstrap'

export default function FormField({ name, label, control, errors, type = 'text', ...rest }) {
  const error = errors?.[name]
  return (
    <Form.Group className="mb-2">
      {label && <Form.Label>{label}</Form.Label>}
      <Controller
        name={name}
        control={control}
        render={({ field }) => (
          <Form.Control {...field} type={type} isInvalid={!!error} {...rest} />
        )}
      />
      {error && <Form.Control.Feedback type="invalid">{error.message}</Form.Control.Feedback>}
    </Form.Group>
  )
}
