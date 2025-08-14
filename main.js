
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


const borrowingCtx = document.getElementById('borrowingChart').getContext('2d');
const borrowingChart = new Chart(borrowingCtx, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Books Borrowed',
            data: [65, 78, 90, 81, 95, 87, 102, 88, 76, 93, 85, 92],
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 1,
            borderRadius: 4,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Area Chart - Library Activity Trends
const activityCtx = document.getElementById('activityChart').getContext('2d');
const activityChart = new Chart(activityCtx, {
    type: 'line',
    data: {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
        datasets: [{
            label: 'New Members',
            data: [12, 19, 15, 25, 22, 30],
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }, {
            label: 'Book Returns',
            data: [18, 25, 20, 32, 28, 35],
            backgroundColor: 'rgba(34, 197, 94, 0.2)',
            borderColor: 'rgba(34, 197, 94, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        elements: {
            point: {
                radius: 4,
                hoverRadius: 6
            }
        }
    }
});



// Initialize charts when the page loads
window.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
});

function initializeCharts() {
    // Bar Chart - Books Borrowed by Category
    const barCtx = document.getElementById('booksBarChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: ['Fiction', 'Science', 'History', 'Technology', 'Biography', 'Arts'],
            datasets: [{
                label: 'Books Borrowed',
                data: [45, 32, 28, 38, 22, 15],
                backgroundColor: [
                    '#4F46E5',
                    '#4F46E5',
                    '#4F46E5',
                    '#4F46E5',
                    '#4F46E5',
                    '#4F46E5'
                ],
                borderColor: [
                    '#4F46E5',
                    '#4F46E5',
                    '#4F46E5',
                    '#4F46E5',
                    '#4F46E5',
                    '#4F46E5'
                ],
                borderWidth: 1,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
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
            }
        }
    });

    // Area Chart - Daily Library Activity
    const areaCtx = document.getElementById('activityAreaChart').getContext('2d');
    new Chart(areaCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Books Borrowed',
                data: [12, 19, 15, 25, 22, 18, 8],
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }, {
                label: 'Books Returned',
                data: [8, 15, 12, 20, 18, 15, 6],
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10B981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
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
            }
        }
    });
}

function fillUpdateForm(book) {
    document.querySelector('input[name="book_id"]').value = book.id;
    document.querySelector('input[name="title"]').value = book.title;
    document.querySelector('input[name="author"]').value = book.author;
    document.querySelector('input[name="category"]').value = book.category;
    document.querySelector('input[name="description"]').value = book.description || '';
    document.querySelector('input[name="publication_year"]').value = book.publication_year;
    document.querySelector('input[name="publisher"]').value = book.publisher || '';
    document.querySelector('input[name="total_copies"]').value = book.total_copies;
    document.querySelector('input[name="available_copies"]').value = book.available_copies;
    document.querySelector('select[name="status"]').value = book.status;

    // Scroll to form
    document.querySelector('input[name="book_id"]').scrollIntoView({behavior: 'smooth'});
}

//  Function to handle book deletion
function deleteBook(bookId) {
    if (confirm('Are you sure you want to delete this book? This action cannot be undone.')) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'book_id';
        input.value = bookId;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}