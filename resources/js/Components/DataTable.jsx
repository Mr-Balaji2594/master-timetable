import { useState, useMemo, useEffect, useRef } from 'react'
import { Card, Form, Row, Col, Pagination, Dropdown, Button } from 'react-bootstrap'
import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  flexRender,
} from '@tanstack/react-table'

const downloadFile = (content, filename, mime = 'text/csv') => {
  const blob = new Blob([content], { type: `${mime};charset=utf-8;` })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

export const formatDate = (value) => {
  if (typeof value === 'string') {
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
      const [y, m, d] = value.split('-')
      return `${d}/${m}/${y}`
    }
    if (/^\d{4}-\d{2}-\d{2}[ T]/.test(value) || /^\d{4}-\d{2}-\d{2}T/.test(value)) {
      const datePart = value.split(/[ T]/)[0]
      const [y, m, d] = datePart.split('-')
      return `${d}/${m}/${y}`
    }
  }
  return value
}

export default function DataTable({
  data = [],
  columns = [],
  searchable = false,
  searchPlaceholder = 'Search records...',
  pageSize = 10,
  pageSizeOptions = [5, 10, 20, 50],
  striped = true,
  hover = true,
  size = 'sm',
  bordered = false,
  emptyMessage = 'No data found',
  showEntries = true,
  showInfo = true,
  enableExport = true,
  enableColumnToggle = true,
  enableReset = true,
  enableColumnFilters = true,
  exportFilename = 'export',
}) {
  const [sorting, setSorting] = useState([])
  const [globalFilter, setGlobalFilter] = useState('')
  const [columnFilters, setColumnFilters] = useState([])
  const [columnVisibility, setColumnVisibility] = useState({})
  const [pagination, setPagination] = useState({ pageIndex: 0, pageSize })

  const formattedColumns = useMemo(() => {
    return columns.map(col => {
      const originalCell = col.cell
      return {
        ...col,
        cell: (info) => {
          const val = info.getValue()
          const formatted = formatDate(val)
          const customInfo = {
            ...info,
            getValue: () => formatted
          }
          if (originalCell) {
            return originalCell(customInfo)
          }
          return formatted
        }
      }
    })
  }, [columns])

  const table = useReactTable({
    data,
    columns: formattedColumns,
    state: { sorting, globalFilter, columnFilters, columnVisibility, pagination },
    onSortingChange: setSorting,
    onGlobalFilterChange: setGlobalFilter,
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    onPaginationChange: setPagination,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    // Don't bounce back to page 1 when data changes after an update / delete
    // / approve / etc. The page index is clamped below instead.
    autoResetPageIndex: false,
  })

  const totalPages = table.getPageCount()
  const currentPage = pagination.pageIndex

  // If the data shrank (e.g. a delete) so the current page no longer exists,
  // move to the last available page instead of showing an empty table.
  useEffect(() => {
    if (totalPages > 0 && currentPage > totalPages - 1) {
      table.setPageIndex(totalPages - 1)
    }
  }, [currentPage, totalPages, table])

  // Go back to page 1 only when the user actively searches / filters / sorts.
  const resetPageOnFilter = useRef([globalFilter, columnFilters, sorting])
  useEffect(() => {
    const prev = resetPageOnFilter.current
    resetPageOnFilter.current = [globalFilter, columnFilters, sorting]
    const changed =
      prev[0] !== globalFilter ||
      prev[1] !== columnFilters ||
      prev[2] !== sorting
    if (changed) {
      table.setPageIndex(0)
    }
  }, [globalFilter, columnFilters, sorting, table])

  const pageRange = useMemo(() => {
    const delta = 2
    const range = []
    for (let i = Math.max(0, currentPage - delta); i <= Math.min(totalPages - 1, currentPage + delta); i++) {
      range.push(i)
    }
    if (range[0] > 0) {
      if (range[0] > 1) range.unshift(-1)
      range.unshift(0)
    }
    if (range[range.length - 1] < totalPages - 1) {
      if (range[range.length - 1] < totalPages - 2) range.push(-1)
      range.push(totalPages - 1)
    }
    return range
  }, [currentPage, totalPages])

  const filteredRows = table.getFilteredRowModel().rows
  const exportableColumns = table.getAllLeafColumns().filter(c => c.getIsVisible() && (c.columnDef.accessorKey || c.columnDef.accessorFn))

  const toExportRows = () => filteredRows.map(row => {
    const cells = {}
    exportableColumns.forEach(col => {
      cells[col.columnDef.header] = formatDate(row.getValue(col.id)) ?? ''
    })
    return cells
  })

  const exportCsv = () => {
    const headers = exportableColumns.map(col => typeof col.columnDef.header === 'string' ? col.columnDef.header : col.id)
    const lines = filteredRows.map(row => headers.map(h => {
      const col = exportableColumns.find(c => (typeof c.columnDef.header === 'string' ? c.columnDef.header : c.id) === h)
      const value = col ? formatDate(row.getValue(col.id)) ?? '' : ''
      return `"${String(value).replace(/"/g, '""')}"`
    }).join(','))
    downloadFile([headers.map(h => `"${h.replace(/"/g, '""')}"`).join(',')].concat(lines).join('\n'), `${exportFilename}_${new Date().toISOString().slice(0, 10)}.csv`)
  }

  const copyTable = async () => {
    const headers = exportableColumns.map(col => typeof col.columnDef.header === 'string' ? col.columnDef.header : col.id)
    const lines = filteredRows.map(row => exportableColumns.map(col => String(formatDate(row.getValue(col.id)) ?? '')).join('\t'))
    const text = [headers.join('\t'), ...lines].join('\n')
    try {
      await navigator.clipboard.writeText(text)
    } catch {
      const ta = document.createElement('textarea')
      ta.value = text
      document.body.appendChild(ta)
      ta.select()
      document.execCommand('copy')
      document.body.removeChild(ta)
    }
  }

  const resetAll = () => {
    setGlobalFilter('')
    setColumnFilters([])
    setSorting([])
    setPagination({ pageIndex: 0, pageSize: pagination.pageSize })
  }

  const canFilterColumn = (column) =>
    enableColumnFilters &&
    column.columnDef.enableColumnFilter !== false &&
    Boolean(column.columnDef.accessorKey || column.columnDef.accessorFn)

  const columnLabel = (column) =>
    typeof column.columnDef.header === 'string' ? column.columnDef.header : column.columnDef.id

  return (
    <div className="dataTables_wrapper">
      <div className="dt-top">
        <div className="dt-top-left">
          {showEntries && (
            <div className="dataTables_length">
              <span>Show</span>
              <Form.Select
                size="sm"
                style={{ width: 'auto', minWidth: '70px' }}
                value={pagination.pageSize}
                onChange={e => setPagination({ pageIndex: 0, pageSize: Number(e.target.value) })}
              >
                {pageSizeOptions.map(opt => (
                  <option key={opt} value={opt}>{opt}</option>
                ))}
              </Form.Select>
              <span>entries</span>
            </div>
          )}
        </div>

        <div className="dt-top-right">
          {searchable && (
            <div className="dataTables_filter">
              <label>
                <span>Search:</span>
                <input
                  type="search"
                  className="dt-search-input"
                  value={globalFilter}
                  onChange={e => setGlobalFilter(e.target.value)}
                  placeholder={searchPlaceholder}
                />
              </label>
            </div>
          )}

          {enableExport || enableColumnToggle || enableReset ? (
            <div className="dataTables_buttons">
              {enableExport && (
                <>
                  <Button size="sm" variant="outline-primary" onClick={exportCsv}>
                    <i className="bi bi-filetype-csv me-1"></i>Export
                  </Button>
                  <Button size="sm" variant="outline-secondary" onClick={copyTable}>
                    <i className="bi bi-clipboard me-1"></i>Copy
                  </Button>
                </>
              )}
              {enableColumnToggle && (
                <Dropdown align="end">
                  <Dropdown.Toggle size="sm" variant="outline-secondary">
                    <i className="bi bi-layout-sidebar me-1"></i>Columns
                  </Dropdown.Toggle>
                  <Dropdown.Menu className="shadow-sm dt-colmenu">
                    {table.getAllLeafColumns().map(col => (
                      <Dropdown.Item as="div" key={col.id} onClick={e => e.preventDefault()}>
                        <Form.Check
                          type="checkbox"
                          label={typeof col.columnDef.header === 'string' ? col.columnDef.header : col.id}
                          checked={col.getIsVisible()}
                          onChange={() => col.toggleVisibility()}
                        />
                      </Dropdown.Item>
                    ))}
                  </Dropdown.Menu>
                </Dropdown>
              )}
              {enableReset && (
                <Button size="sm" variant="outline-danger" onClick={resetAll}>
                  <i className="bi bi-arrow-counterclockwise me-1"></i>Reset
                </Button>
              )}
            </div>
          ) : null}
        </div>
      </div>

      <div className="table-responsive">
        <table className={`table dt-table${striped ? ' table-striped' : ''}${hover ? ' table-hover' : ''}${bordered ? ' table-bordered' : ''}${size === 'sm' ? ' table-sm' : ''}`}>
          <thead className="table-light">
            {table.getHeaderGroups().map(headerGroup => (
              <tr key={headerGroup.id}>
                {headerGroup.headers.map(header => (
                  <th
                    key={header.id}
                    onClick={header.column.getToggleSortingHandler()}
                    style={{ cursor: header.column.getCanSort() ? 'pointer' : 'default', whiteSpace: 'nowrap', userSelect: 'none' }}
                  >
                    {flexRender(header.column.columnDef.header, header.getContext())}
                    {header.column.getCanSort() && (
                      <span className="ms-1" style={{ opacity: header.column.getIsSorted() ? 0.7 : 0.25 }}>
                        {header.column.getIsSorted() === 'asc' ? '\u2191' : header.column.getIsSorted() === 'desc' ? '\u2193' : '\u2195'}
                      </span>
                    )}
                  </th>
                ))}
              </tr>
            ))}
            {enableColumnFilters && table.getHeaderGroups().some(g => g.headers.some(h => canFilterColumn(h.column))) && (
              <tr className="dt-col-filters">
                {table.getHeaderGroups()[0].headers.map(header => (
                  <th key={header.id} className="p-1">
                    {canFilterColumn(header.column) ? (
                      <input
                        className="form-control form-control-sm dt-col-filter"
                        placeholder={`Filter ${typeof header.column.columnDef.header === 'string' ? header.column.columnDef.header : header.column.id}...`}
                        value={header.column.getFilterValue() ?? ''}
                        onChange={e => header.column.setFilterValue(e.target.value)}
                      />
                    ) : null}
                  </th>
                ))}
              </tr>
            )}
          </thead>
          <tbody>
            {table.getRowModel().rows.length > 0 ? (
              table.getRowModel().rows.map(row => (
                <tr key={row.id}>
                  {row.getVisibleCells().map(cell => (
                    <td key={cell.id} data-label={columnLabel(cell.column)}>
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan={table.getAllLeafColumns().filter(c => c.getIsVisible()).length}>
                  <div className="empty-state">
                    <i className="bi bi-inbox"></i>
                    <p>{emptyMessage}</p>
                  </div>
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <div className="dt-bottom">
        {showInfo && (
          <div className="dataTables_info">
            {table.getFilteredRowModel().rows.length} total entries
          </div>
        )}
        {totalPages > 1 && (
          <Pagination size="sm" className="mb-0 dataTables_paginate">
            <Pagination.First onClick={() => table.setPageIndex(0)} disabled={currentPage === 0} aria-label="First page" />
            <Pagination.Prev onClick={() => table.previousPage()} disabled={currentPage === 0} aria-label="Previous page" />
            {pageRange.map((page, i) =>
              page === -1 ? (
                <Pagination.Ellipsis key={`e${i}`} disabled />
              ) : (
                <Pagination.Item key={page} active={page === currentPage} onClick={() => table.setPageIndex(page)}>
                  {page + 1}
                </Pagination.Item>
              )
            )}
            <Pagination.Next onClick={() => table.nextPage()} disabled={currentPage >= totalPages - 1} aria-label="Next page" />
            <Pagination.Last onClick={() => table.setPageIndex(totalPages - 1)} disabled={currentPage >= totalPages - 1} aria-label="Last page" />
          </Pagination>
        )}
      </div>
    </div>
  )
}
