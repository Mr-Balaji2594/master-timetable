import Select from 'react-select'

const customStyles = {
    control: (base, state) => ({
        ...base,
        minHeight: 38,
        borderColor: state.isFocused ? '#4f46e5' : '#e2e8f0',
        boxShadow: state.isFocused ? '0 0 0 3px rgba(79,70,229,0.12)' : 'none',
        '&:hover': { borderColor: state.isFocused ? '#4f46e5' : '#cbd5e1' },
        fontSize: '0.875rem',
        borderRadius: '6px',
    }),
    placeholder: (base) => ({ ...base, color: '#94a3b8', fontSize: '0.875rem' }),
    input: (base) => ({ ...base, fontSize: '0.875rem', color: '#0f172a' }),
    singleValue: (base) => ({ ...base, color: '#0f172a', fontSize: '0.875rem' }),
    menu: (base) => ({ ...base, zIndex: 9999, fontSize: '0.875rem', borderRadius: '8px', boxShadow: '0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04)' }),
    menuList: (base) => ({ ...base, maxHeight: 220, overflowY: 'auto' }),
    option: (base, state) => ({
        ...base,
        backgroundColor: state.isSelected ? '#4f46e5' : state.isFocused ? 'rgba(79,70,229,0.08)' : 'transparent',
        color: state.isSelected ? '#fff' : '#0f172a',
        fontSize: '0.875rem',
    }),
    clearIndicator: (base) => ({ ...base, color: '#94a3b8', '&:hover': { color: '#64748b' } }),
    dropdownIndicator: (base) => ({ ...base, color: '#94a3b8', '&:hover': { color: '#64748b' } }),
}

function toOptions(options) {
    if (!options) return []
    if (Array.isArray(options) && options.length > 0 && typeof options[0] === 'object' && 'value' in options[0]) {
        return options
    }
    if (Array.isArray(options) && options.length > 0 && typeof options[0] === 'string') {
        return options.map(o => ({ value: o, label: o }))
    }
    return options
}

export default function Select2({ options, value, onChange, placeholder = 'Select...', isClearable = true, isSearchable = true, required, name, className, isDisabled }) {
    const opts = toOptions(options)
    const selected = opts.find(o => String(o.value) === String(value)) || null

    return (
        <Select
            options={opts}
            value={selected}
            onChange={option => onChange(option ? option.value : '')}
            placeholder={placeholder}
            isClearable={isClearable}
            isSearchable={isSearchable}
            isDisabled={isDisabled}
            name={name}
            styles={customStyles}
            className={className}
            aria-required={required}
            maxMenuHeight={220}
            noOptionsMessage={() => 'No options'}
        />
    )
}
