import { Controller } from 'react-hook-form'
import { Form } from 'react-bootstrap'
import Select2 from './Select2'

export default function Select2Field({ name, label, control, errors, options, placeholder, isClearable = true, ...rest }) {
  const error = errors?.[name]
  return (
    <Form.Group className="mb-2">
      {label && <Form.Label>{label}</Form.Label>}
      <Controller
        name={name}
        control={control}
        render={({ field }) => (
          <Select2
            value={field.value}
            onChange={v => field.onChange(String(v ?? ''))}
            options={options}
            placeholder={placeholder || (label ? `Select ${label}` : 'Select')}
            isClearable={isClearable}
            {...rest}
          />
        )}
      />
      {error && <div className="invalid-feedback d-block">{error.message}</div>}
    </Form.Group>
  )
}
