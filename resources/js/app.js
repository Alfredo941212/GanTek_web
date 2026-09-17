import Chart from 'chart.js/auto';

const filters = document.querySelector('#production-filters');
if (filters) {
    const period = filters.querySelector('#periodo');
    const from = filters.querySelector('#desde');
    const to = filters.querySelector('#hasta');
    const updateDates = () => {
        const custom = period.value === 'personalizado';
        for (const input of [from, to]) {
            input.disabled = !custom;
            input.required = custom;
        }
        if (from.value) to.min = from.value;
        else to.removeAttribute('min');
    };
    period.addEventListener('change', updateDates);
    from.addEventListener('change', updateDates);
    updateDates();
}

const canvas = document.querySelector('#graficaProduccion');
if (canvas) {
    const status = document.querySelector('#chart-status');
    try {
        const labels = JSON.parse(canvas.dataset.labels);
        const values = JSON.parse(canvas.dataset.values);
        new Chart(canvas, {
            type: values.length === 1 ? 'bar' : 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Producción (L)',
                    data: values,
                    borderColor: '#196b49',
                    backgroundColor: 'rgba(25, 107, 73, 0.18)',
                    pointBackgroundColor: '#196b49',
                    borderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    maxBarThickness: 80,
                    tension: 0,
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: context => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(context.parsed.y) + ' L',
                        },
                    },
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Litros' } },
                    x: { title: { display: true, text: 'Fecha con registros' }, ticks: { maxTicksLimit: 12 } },
                },
            },
        });
        status.hidden = true;
    } catch (error) {
        status.textContent = 'No se pudo mostrar la gráfica. Consulta los valores en la tabla Por día.';
        status.className = 'alert error';
        console.error('Error al mostrar la producción:', error);
    }
}
