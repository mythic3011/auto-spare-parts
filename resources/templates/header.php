<?php
// Header template - displays navigation/branding
$userRole = isset($userRole) ? $userRole : 'guest';
$userName = isset($userName) ? htmlspecialchars($userName) : 'User';
$basePath = isset($basePath) ? $basePath : '/';
?>

<header class="bg-white dark:bg-gray-800 shadow-md">
    <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
        <!-- Logo/Brand -->
        <div class="flex items-center gap-3">
            <img src="<?php echo $basePath; ?>resources/image/SLMC_Icon_login.jpg" 
                 alt="SLMC Logo" class="h-8 w-8 rounded">
            <span class="font-bold text-xl text-gray-900 dark:text-white">
                Smart & Luxury Motor Company
            </span>
        </div>
        
        <!-- Navigation Links (role-based) -->
        <ul class="flex items-center gap-6">
            <?php if ($userRole === 'dealer'): ?>
                <li><a href="<?php echo $basePath; ?>page/Dealer/viewSparePart.php" 
                        class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    Spare Parts
                </a></li>
                <li><a href="<?php echo $basePath; ?>page/Dealer/shoppingCart.php" 
                        class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    Cart
                </a></li>
                <li><a href="<?php echo $basePath; ?>page/Dealer/viewOrderRecord.html" 
                        class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    Orders
                </a></li>
                <li><a href="<?php echo $basePath; ?>page/Dealer/profile.html" 
                        class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    Profile
                </a></li>
            <?php elseif ($userRole === 'manager'): ?>
                <li><a href="<?php echo $basePath; ?>page/Staff/Item.html" 
                        class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    Inventory
                </a></li>
                <li><a href="<?php echo $basePath; ?>page/Staff/order.html" 
                        class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    Orders
                </a></li>
                <li><a href="<?php echo $basePath; ?>page/Staff/genreport.html" 
                        class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    Reports
                </a></li>
            <?php endif; ?>
            
            <!-- User Menu -->
            <li class="relative group">
                <button class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition">
                    <?php echo $userName; ?> ▼
                </button>
                <div class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white dark:bg-gray-700 shadow-lg rounded-lg">
                    <a href="<?php echo $basePath; ?>page/Dealer/profile.html" 
                       class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                        Profile
                    </a>
                    <a href="<?php echo $basePath; ?>logout.html" 
                       class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600">
                        Logout
                    </a>
                </div>
            </li>
        </ul>
    </nav>
</header>
