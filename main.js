
document.addEventListener('DOMContentLoaded', function () {
    initializeIndigoChart();
});

function initializeIndigoChart() {
    const indigoCtx = document.getElementById('indigoLineChart').getContext('2d');
    new Chart(indigoCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Books Borrowed',
                data: [15, 22, 18, 29, 20, 25],
                borderColor: '#4F46E5',  // Indigo-600
                backgroundColor: 'rgba(79, 70, 229, 0.2)', // Indigo with opacity
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#F3F4F6'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}


document.addEventListener('DOMContentLoaded', function() {
    // Initialize main calendar
    flatpickr("#calendar-main", {
        inline: true,
        disableMobile: "true",
        monthSelectorType: "static",
    });

    setTimeout(() => {
        const calendar = document.querySelector('#calendar-main .flatpickr-calendar');
        if (calendar) {
            calendar.style.boxShadow = 'none';
            calendar.style.border = 'none';
            calendar.style.backgroundColor = 'transparent';
        }
    }, 100);
});


// Pie Chart for Book Categories
const pieCtx = document.getElementById('bookCategoriesPieChart').getContext('2d');
const bookCategoriesPieChart = new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: ['Fiction', 'Science', 'History', 'Biography'],
        datasets: [{
            data: [35, 25, 20, 20],
            backgroundColor: [
                '#4F46E5', // Blue
                '#10B981', // Green
                '#F59E0B', // Yellow
                '#8B5CF6'  // Purple
            ],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false // We're using custom legend below
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + '%';
                    }
                }
            }
        }
    }
});