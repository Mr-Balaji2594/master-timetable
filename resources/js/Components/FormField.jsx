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
        render={({ field }) => {
          const handleChange = (e) => {
            let val = e.target.value
            if (type === 'date' && val) {
              const parts = val.split('-')
              if (parts[0] && parts[0].length > 4) {
                parts[0] = parts[0].slice(0, 4)
                val = parts.join('-')
                e.target.value = val
              }
            }
            field.onChange(val)
          }

          const extra = type === 'date' ? { max: '9999-12-31' } : {}

          return (
            <Form.Control 
              {...field} 
              onChange={handleChange} 
              type={type} 
              isInvalid={!!error} 
              {...extra} 
              {...rest} 
            />
          )
        }}
      />
      {error && <Form.Control.Feedback type="invalid">{error.message}</Form.Control.Feedback>}
    </Form.Group>
  )
}
