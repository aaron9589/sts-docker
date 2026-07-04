function detachStationFilters(tableId, filtersId, mountId) {
  const filters = document.getElementById(filtersId);
  const mount = document.getElementById(mountId);
  const table = document.getElementById(tableId);

  if (table) {
    const filterRow = table.querySelector('tr.table-filter-row');
    if (filterRow) {
      filterRow.remove();
    }
  }

  if (filters && mount && filters.parentElement !== mount) {
    mount.appendChild(filters);
  }

  if (filters) {
    filters.classList.add('d-none');
  }
}

function integrateStationFiltersIntoTable(tableId, filtersId, mountId) {
  const table = document.getElementById(tableId);
  const filters = document.getElementById(filtersId);
  if (!table || !filters) {
    return;
  }

  detachStationFilters(tableId, filtersId, mountId);

  const headerRows = Array.from(table.querySelectorAll('tr')).filter(function(row) {
    return row.querySelector('th');
  });
  const headerRow = headerRows[headerRows.length - 1];
  if (!headerRow) {
    return;
  }

  const filterRow = document.createElement('tr');
  filterRow.className = 'table-filter-row noprint';
  const cell = document.createElement('td');
  cell.colSpan = headerRow.cells.length;
  cell.className = 'table-filters-cell';
  filterRow.appendChild(cell);
  headerRow.insertAdjacentElement('afterend', filterRow);
  cell.appendChild(filters);
  filters.classList.remove('d-none');
}
