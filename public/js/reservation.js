(function () {
    const seatMap = document.getElementById('seat-map');
    const form = document.getElementById('reservation-form');
    const seatInputs = document.getElementById('seat-inputs');
    const btnConfirm = document.getElementById('btn-confirm');
    const btnConfirmMobile = document.getElementById('btn-confirm-mobile');
    const config = window.RESERVATION_CONFIG || {};

    if (!seatMap || !form) {
        return;
    }

    const rows = JSON.parse(seatMap.dataset.rows || '[]');
    const cols = parseInt(seatMap.dataset.cols || '8', 10);
    const aisleAfter = parseInt(seatMap.dataset.aisleAfter || '2', 10);
    let occupied = new Set(JSON.parse(seatMap.dataset.occupied || '[]'));
    const selected = new Set();

    const selectedLabel = document.getElementById('selected-seats-label');
    const selectedSubtotal = document.getElementById('selected-seats-subtotal');
    const ticketsLine = document.getElementById('tickets-line');
    const ticketsAmount = document.getElementById('tickets-amount');
    const totalPrice = document.getElementById('total-price');
    const mobileCountLabel = document.getElementById('mobile-count-label');
    const mobileTotal = document.getElementById('mobile-total');

    function formatMoney(value) {
        return '$' + value.toFixed(2);
    }

    function formatSeatsLabel(sorted) {
        if (sorted.length === 0) {
            return 'None';
        }
        const byRow = {};
        sorted.forEach((seat) => {
            const row = seat[0];
            const num = seat.slice(1);
            if (!byRow[row]) {
                byRow[row] = [];
            }
            byRow[row].push(num);
        });
        return Object.keys(byRow)
            .sort()
            .map((row) => `Row ${row}, Seat ${byRow[row].join(', ')}`)
            .join(' • ');
    }

    function seatId(row, col) {
        return row + col;
    }

    function appendAisleCell(grid) {
        const aisle = document.createElement('div');
        aisle.className = 'seat-aisle';
        aisle.setAttribute('aria-hidden', 'true');
        grid.appendChild(aisle);
    }

    function renderMap() {
        seatMap.innerHTML = '';

        const grid = document.createElement('div');
        grid.className = 'seat-grid';
        grid.style.setProperty('--seat-cols-left', aisleAfter);
        grid.style.setProperty('--seat-cols-right', cols - aisleAfter);

        const corner = document.createElement('div');
        corner.className = 'seat-grid-corner';
        grid.appendChild(corner);

        for (let c = 1; c <= cols; c++) {
            if (c === aisleAfter + 1) {
                appendAisleCell(grid);
            }
            const colLabel = document.createElement('div');
            colLabel.className = 'seat-col-label';
            colLabel.textContent = c;
            grid.appendChild(colLabel);
        }

        rows.forEach((row) => {
            const rowLabel = document.createElement('div');
            rowLabel.className = 'seat-row-label';
            rowLabel.textContent = row;
            grid.appendChild(rowLabel);

            for (let c = 1; c <= cols; c++) {
                if (c === aisleAfter + 1) {
                    appendAisleCell(grid);
                }

                const id = seatId(row, c);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'seat-btn';
                btn.dataset.seat = id;
                btn.textContent = c;
                btn.setAttribute('aria-label', `Seat ${id}`);

                if (occupied.has(id)) {
                    btn.classList.add('taken');
                    btn.disabled = true;
                } else if (selected.has(id)) {
                    btn.classList.add('selected');
                } else {
                    btn.classList.add('available');
                }

                btn.addEventListener('click', () => toggleSeat(id));
                grid.appendChild(btn);
            }
        });

        seatMap.appendChild(grid);
    }

    function toggleSeat(id) {
        if (occupied.has(id)) {
            return;
        }

        if (selected.has(id)) {
            selected.delete(id);
        } else {
            selected.add(id);
        }

        renderMap();
        updateSummary();
    }

    function updateSummary() {
        const count = selected.size;
        const ticketsTotal = count * (config.ticketPrice || 0);
        const fee = config.bookingFee || 0;
        const total = count > 0 ? ticketsTotal + fee : 0;

        const sorted = Array.from(selected).sort();
        const label = formatSeatsLabel(sorted);

        if (selectedLabel) {
            selectedLabel.textContent = label;
        }
        if (selectedSubtotal) {
            selectedSubtotal.textContent = formatMoney(ticketsTotal);
        }
        if (ticketsLine) {
            ticketsLine.textContent = `${count}x Adult Tickets`;
        }
        if (ticketsAmount) {
            ticketsAmount.textContent = formatMoney(ticketsTotal);
        }
        if (totalPrice) {
            totalPrice.textContent = formatMoney(total);
        }
        if (mobileCountLabel) {
            mobileCountLabel.textContent = `SELECTED SEATS (${count})`;
        }
        if (mobileTotal) {
            mobileTotal.textContent = formatMoney(total);
        }

        seatInputs.innerHTML = '';
        sorted.forEach((seat) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'seats[]';
            input.value = seat;
            seatInputs.appendChild(input);
        });

        const enabled = count > 0;
        if (btnConfirm) {
            btnConfirm.disabled = !enabled;
        }
        if (btnConfirmMobile) {
            btnConfirmMobile.disabled = !enabled;
        }
    }

    async function refreshOccupied() {
        try {
            const response = await fetch(config.occupiedUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            const next = new Set(data.occupied || []);

            let changed = next.size !== occupied.size;
            if (!changed) {
                for (const seat of next) {
                    if (!occupied.has(seat)) {
                        changed = true;
                        break;
                    }
                }
            }

            if (!changed) {
                return;
            }

            occupied = next;
            for (const seat of selected) {
                if (occupied.has(seat)) {
                    selected.delete(seat);
                }
            }
            renderMap();
            updateSummary();
        } catch (e) {
   
        }
    }

    form.addEventListener('submit', (e) => {
        if (selected.size === 0) {
            e.preventDefault();
            return;
        }
        for (const seat of selected) {
            if (occupied.has(seat)) {
                e.preventDefault();
                alert('A selected seat was just booked by someone else. Please refresh and choose different seats.');
                refreshOccupied();
                return;
            }
        }
    });

    renderMap();
    updateSummary();

    if (config.occupiedUrl && config.pollIntervalMs) {
        setInterval(refreshOccupied, config.pollIntervalMs);
    }
})();
