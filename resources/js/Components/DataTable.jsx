import { useState, useMemo } from 'react'
import { Card, Form, Row, Col, Pagination } from 'react-bootstrap'
import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  flexRender,
} from '@tanstack/react-table'

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
}) {
  const [sorting, setSorting] = useState([])
  const [globalFilter, setGlobalFilter] = useState('')
  const [pagination, setPagination] = useState({ pageIndex: 0, pageSize })

  const table = useReactTable({
    data,
    columns,
    state: { sorting, globalFilter, pagination },
    onSortingChange: setSorting,
    onGlobalFilterChange: setGlobalFilter,
    onPaginationChange: setPagination,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
  })

  const totalPages = table.getPageCount()
  const currentPage = pagination.pageIndex

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

  return (
    <div className="dataTables_wrapper">
      <div className="dt-top">
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
      </div>

      <div className="table-responsive">
        <table className={`table${striped ? ' table-striped' : ''}${hover ? ' table-hover' : ''}${bordered ? ' table-bordered' : ''}${size === 'sm' ? ' table-sm' : ''}`}>
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
          </thead>
          <tbody>
            {table.getRowModel().rows.length > 0 ? (
              table.getRowModel().rows.map(row => (
                <tr key={row.id}>
                  {row.getVisibleCells().map(cell => (
                    <td key={cell.id}>
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))
            ) : (
              <tr>
                <td colSpan={columns.length}>
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

      {totalPages > 1 && (
        <div className="dt-bottom">
          <div className="dataTables_info">
            {table.getFilteredRowModel().rows.length} total entries
          </div>
          <Pagination size="sm" className="mb-0 dataTables_paginate">
            <Pagination.First onClick={() => table.setPageIndex(0)} disabled={currentPage === 0} />
            <Pagination.Prev onClick={() => table.previousPage()} disabled={currentPage === 0} />
            {pageRange.map((page, i) =>
              page === -1 ? (
                <Pagination.Ellipsis key={`e${i}`} disabled />
              ) : (
                <Pagination.Item key={page} active={page === currentPage} onClick={() => table.setPageIndex(page)}>
                  {page + 1}
                </Pagination.Item>
              )
            )}
            <Pagination.Next onClick={() => table.nextPage()} disabled={currentPage >= totalPages - 1} />
            <Pagination.Last onClick={() => table.setPageIndex(totalPages - 1)} disabled={currentPage >= totalPages - 1} />
          </Pagination>
        </div>
      )}
    </div>
  )
}
