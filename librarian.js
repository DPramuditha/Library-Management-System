function fillUpdateForm(id, name, email, phone, address, role) {
    document.getElementById('update_user_id').value = id;
    document.getElementById('update_name').value = name;
    document.getElementById('update_email').value = email;
    document.getElementById('update_phone').value = phone;
    document.getElementById('update_address').value = address;
    document.getElementById('update_role').value = role;

    // Scroll to the form
    document.getElementById('update_user_id').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function clearUpdateForm() {
    document.getElementById('update_user_id').value = '';
    document.getElementById('update_name').value = '';
    document.getElementById('update_email').value = '';
    document.getElementById('update_phone').value = '';
    document.getElementById('update_address').value = '';
    document.getElementById('update_role').value = '';
}

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

function fillBookUpdateForm(book) {
    // Fill the book update form with book data
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

    // Scroll to the form
    document.querySelector('input[name="book_id"]').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
