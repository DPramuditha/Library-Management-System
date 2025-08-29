<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "libraryMS";

$conn = new mysqli($servername, $username, $password, $dbname);
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

function getAllBooks($conn)
{
    $sql = "SELECT * FROM books ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $books = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    return $books;
}

function getAvailableBooks($conn)
{
    $sql = "SELECT * FROM books WHERE available_copies > 0 ORDER BY title";
    $result = $conn->query($sql);
    $books = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    return $books;
}

function searchBooks($conn, $searchTerm)
{
    $searchTerm = $conn->real_escape_string($searchTerm);
    $sql = "SELECT * FROM books WHERE title LIKE '%$searchTerm%' OR author LIKE '%$searchTerm%' OR isbn LIKE '%$searchTerm%' ORDER BY title";
    $result = $conn->query($sql);
    $books = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
    return $books;
}

// Fetch books for different sections
$topBooks = getAllBooks($conn);
$availableBooks = getAvailableBooks($conn);

// Handle search
$searchResults = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchResults = searchBooks($conn, $_GET['search']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="index.css" rel="stylesheet">
    <title>Student Dashboard</title>
</head>
<body>
<!-- Top Books Section -->
<div class="mt-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Top Books</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach (array_slice($topBooks, 0, 4) as $book): ?>
            <div class="bg-white rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out overflow-hidden">
                <div class="h-48 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                    <?php if (!empty($book['cover_image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($book['cover_image_url']); ?>"
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             class="h-40 w-28 object-cover rounded shadow-lg"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <?php endif; ?>
                    <div class="h-40 w-28 bg-blue-500 text-white rounded shadow-lg flex items-center justify-center text-xs font-medium text-center p-2"
                         style="<?php echo !empty($book['cover_image_url']) ? 'display: none;' : ''; ?>">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-lg font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <span class="relative flex size-3">
                                <?php if ($book['available_copies'] > 0): ?>
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex size-3 rounded-full bg-green-500"></span>
                                <?php else: ?>
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex size-3 rounded-full bg-red-500"></span>
                                <?php endif; ?>
                            </span>
                    </div>
                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                    <p class="text-xs <?php echo $book['available_copies'] > 0 ? 'text-gray-500' : 'text-red-500'; ?> mb-3">
                        <?php echo htmlspecialchars($book['category']); ?> •
                        <?php echo $book['available_copies'] > 0 ? 'Available (' . $book['available_copies'] . ')' : 'Borrowed'; ?>
                    </p>
                    <?php if ($book['available_copies'] > 0): ?>
                        <button onclick="borrowBook(<?php echo $book['id']; ?>)"
                                class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            Borrow Book
                        </button>
                    <?php else: ?>
                        <button class="w-full px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed" disabled>
                            Currently Borrowed
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Search Section -->
<div id="search-section" class="content-section">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Search Books</h1>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <form method="GET" action="">
            <input type="hidden" name="section" value="search">
            <div class="flex space-x-4">
                <div class="relative flex-1">
                    <input type="text" name="search"
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           placeholder="Search by title, author, or ISBN..."
                           class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium text-gray-700">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 text-gray-400">
                            <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors hover:scale-105 transition duration-300 ease-in-out">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 inline-block ml-3 mr-3">
                        <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
                    </svg>
                    Search
                </button>
            </div>
        </form>
    </div>

    <div class="mt-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            $booksToShow = !empty($searchResults) ? $searchResults : $availableBooks;
            foreach ($booksToShow as $book):
                ?>
                <div class="bg-white rounded-lg shadow-md hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-in-out overflow-hidden">
                    <div class="h-48 bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                        <?php if (!empty($book['cover_image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($book['cover_image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 class="h-40 w-28 object-cover rounded shadow-lg"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                        <?php endif; ?>
                        <div class="h-40 w-28 bg-blue-500 text-white rounded shadow-lg flex items-center justify-center text-xs font-medium text-center p-2"
                             style="<?php echo !empty($book['cover_image_url']) ? 'display: none;' : ''; ?>">
                            <?php echo htmlspecialchars($book['title']); ?>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-lg font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <span class="relative flex size-3">
                                    <?php if ($book['available_copies'] > 0): ?>
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                        <span class="relative inline-flex size-3 rounded-full bg-green-500"></span>
                                    <?php else: ?>
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex size-3 rounded-full bg-red-500"></span>
                                    <?php endif; ?>
                                </span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                        <p class="text-xs <?php echo $book['available_copies'] > 0 ? 'text-gray-500' : 'text-red-500'; ?> mb-3">
                            <?php echo htmlspecialchars($book['category']); ?> •
                            <?php echo $book['available_copies'] > 0 ? 'Available (' . $book['available_copies'] . ')' : 'Borrowed'; ?>
                        </p>
                        <?php if ($book['available_copies'] > 0): ?>
                            <button onclick="borrowBook(<?php echo $book['id']; ?>)"
                                    class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                Borrow Book
                            </button>
                        <?php else: ?>
                            <button class="w-full px-4 py-2 bg-gray-400 text-white text-sm font-medium rounded-lg cursor-not-allowed" disabled>
                                Currently Borrowed
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function borrowBook(bookId) {
        if (confirm('Do you want to borrow this book?')) {
            fetch('borrow_book.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    book_id: bookId,
                    student_id: 1
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Book borrowed successfully!');
                        location.reload();
                    } else {
                        alert('Error borrowing book: ' + data.message);
                    }
                });
        }
    }
</script>
</body>
</html>