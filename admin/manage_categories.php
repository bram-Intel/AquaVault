<?php
/**
 * AquaVault Capital - Manage Investment Categories
 */
session_start();
require_once '../db/connect.php';
require_once '../includes/auth.php';

// Check if admin is logged in
require_admin();

$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO investment_categories 
                        (name, slug, description, icon, color, min_amount, max_amount, is_active, sort_order) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        sanitize_input($_POST['name']),
                        sanitize_input($_POST['slug']),
                        sanitize_input($_POST['description']),
                        sanitize_input($_POST['icon']),
                        sanitize_input($_POST['color']),
                        (float)$_POST['min_amount'],
                        !empty($_POST['max_amount']) ? (float)$_POST['max_amount'] : null,
                        isset($_POST['is_active']) ? 1 : 0,
                        (int)$_POST['sort_order']
                    ]);
                    $message = 'Investment category created successfully!';
                } catch (PDOException $e) {
                    error_log("Category creation error: " . $e->getMessage());
                    $error = 'Failed to create investment category.';
                }
                break;
                
            case 'update':
                try {
                    $stmt = $pdo->prepare("
                        UPDATE investment_categories 
                        SET name = ?, slug = ?, description = ?, icon = ?, color = ?, 
                            min_amount = ?, max_amount = ?, is_active = ?, sort_order = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        sanitize_input($_POST['name']),
                        sanitize_input($_POST['slug']),
                        sanitize_input($_POST['description']),
                        sanitize_input($_POST['icon']),
                        sanitize_input($_POST['color']),
                        (float)$_POST['min_amount'],
                        !empty($_POST['max_amount']) ? (float)$_POST['max_amount'] : null,
                        isset($_POST['is_active']) ? 1 : 0,
                        (int)$_POST['sort_order'],
                        (int)$_POST['category_id']
                    ]);
                    $message = 'Investment category updated successfully!';
                } catch (PDOException $e) {
                    error_log("Category update error: " . $e->getMessage());
                    $error = 'Failed to update investment category.';
                }
                break;
                
            case 'toggle_status':
                try {
                    $stmt = $pdo->prepare("UPDATE investment_categories SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([(int)$_POST['category_id']]);
                    $message = 'Category status updated successfully!';
                } catch (PDOException $e) {
                    error_log("Category status toggle error: " . $e->getMessage());
                    $error = 'Failed to update category status.';
                }
                break;
        }
    }
}

// Get all investment categories
try {
    $stmt = $pdo->prepare("
        SELECT ic.*, COUNT(id.id) as duration_count
        FROM investment_categories ic
        LEFT JOIN investment_durations id ON ic.id = id.category_id
        GROUP BY ic.id
        ORDER BY ic.sort_order ASC, ic.name ASC
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories fetch error: " . $e->getMessage());
    $categories = [];
}

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    foreach ($categories as $category) {
        if ($category['id'] == $edit_id) {
            $edit_category = $category;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Investment Categories - AquaVault Capital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #007BFF 0%, #28A745 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Admin Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="gradient-bg w-8 h-8 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-bold">AV</span>
                    </div>
                    <span class="ml-2 text-xl font-bold text-gray-900">AquaVault Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600">Dashboard</a>
                    <a href="logout.php" class="text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Manage Investment Categories</h1>
            <p class="mt-2 text-gray-600">Create and manage investment categories for users</p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-600 text-sm"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-600 text-sm"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Create/Edit Category Form -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">
                <?php echo $edit_category ? 'Edit Investment Category' : 'Create New Investment Category'; ?>
            </h2>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="<?php echo $edit_category ? 'update' : 'create'; ?>">
                <?php if ($edit_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                        <input type="text" id="name" name="name" required
                               value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug (URL-friendly)</label>
                        <input type="text" id="slug" name="slug" required
                               value="<?php echo htmlspecialchars($edit_category['slug'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="icon" class="block text-sm font-medium text-gray-700 mb-2">Icon (Emoji)</label>
                        <input type="text" id="icon" name="icon" 
                               value="<?php echo htmlspecialchars($edit_category['icon'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-700 mb-2">Color (Hex)</label>
                        <input type="color" id="color" name="color" 
                               value="<?php echo htmlspecialchars($edit_category['color'] ?? '#007BFF'); ?>"
                               class="w-full h-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" 
                               value="<?php echo $edit_category['sort_order'] ?? '0'; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="min_amount" class="block text-sm font-medium text-gray-700 mb-2">Minimum Amount (₦)</label>
                        <input type="number" id="min_amount" name="min_amount" required min="1000" step="1000"
                               value="<?php echo $edit_category['min_amount'] ?? ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="max_amount" class="block text-sm font-medium text-gray-700 mb-2">Maximum Amount (₦) - Optional</label>
                        <input type="number" id="max_amount" name="max_amount" min="1000" step="1000"
                               value="<?php echo $edit_category['max_amount'] ?? ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="is_active" name="is_active" 
                           <?php echo ($edit_category['is_active'] ?? 1) ? 'checked' : ''; ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Active (available for users to invest)
                    </label>
                </div>

                <div class="flex gap-4">
                    <button type="submit" 
                            class="gradient-bg text-white px-6 py-2 rounded-lg font-medium hover:opacity-90 transition duration-200">
                        <?php echo $edit_category ? 'Update Category' : 'Create Category'; ?>
                    </button>
                    <?php if ($edit_category): ?>
                        <a href="manage_categories.php" 
                           class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                            Cancel
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Categories List -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Investment Categories</h2>
            
            <?php if (empty($categories)): ?>
                <div class="text-center py-8">
                    <div class="text-4xl mb-4">📊</div>
                    <p class="text-gray-500">No investment categories created yet</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Range</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durations</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-2xl mr-3"><?php echo $category['icon']; ?></div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($category['slug']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ₦<?php echo number_format($category['min_amount']); ?>
                                        <?php if ($category['max_amount']): ?>
                                            - ₦<?php echo number_format($category['max_amount']); ?>
                                        <?php else: ?>
                                            +
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $category['duration_count']; ?> durations
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $category['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <a href="manage_durations.php?category_id=<?php echo $category['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">Manage Durations</a>
                                        <a href="manage_categories.php?edit=<?php echo $category['id']; ?>" 
                                           class="text-green-600 hover:text-green-900">Edit</a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" 
                                                    class="text-yellow-600 hover:text-yellow-900">
                                                <?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
