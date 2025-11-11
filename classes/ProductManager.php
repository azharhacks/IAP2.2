<?php
/**
 * Product Management Class
 * Handles all product-related operations for the e-commerce system
 * 
 * Features:
 * - Product catalog browsing
 * - Product search and filtering
 * - Product details and reviews
 * - Related products suggestions
 */

class ProductManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get featured products for homepage
     * @param int $limit Number of products to return
     * @return array Featured products
     */
    public function getFeaturedProducts($limit = 6) {
        // Ensure limit is an integer to prevent SQL injection
        $limit = (int)$limit;
        
        $stmt = $this->pdo->prepare("
            SELECT p.*, b.name as brand_name, c.name as category_name,
                   pi.image_url, pi.alt_text,
                   ROUND(AVG(r.rating), 1) as avg_rating,
                   COUNT(r.id) as review_count
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
            LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = TRUE
            WHERE p.is_featured = TRUE AND p.is_active = TRUE AND p.stock_quantity > 0
            GROUP BY p.id
            ORDER BY p.created_at DESC
            LIMIT " . $limit . "
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all products with pagination and filtering
     * @param array $filters Search filters
     * @param int $page Current page
     * @param int $perPage Products per page
     * @return array Products and pagination info
     */
    public function getProducts($filters = [], $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        $whereConditions = ["p.is_active = TRUE"];
        $params = [];
        
        // Add filters
        if (!empty($filters['search'])) {
            $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['category_id'])) {
            $whereConditions[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['brand_id'])) {
            $whereConditions[] = "p.brand_id = ?";
            $params[] = $filters['brand_id'];
        }
        
        if (!empty($filters['min_price'])) {
            $whereConditions[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $whereConditions[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE $whereClause
        ");
        $countStmt->execute($params);
        $totalProducts = $countStmt->fetch()['total'];
        
        // Get products
        $orderBy = $filters['sort'] ?? 'p.created_at DESC';
        $stmt = $this->pdo->prepare("
            SELECT p.*, b.name as brand_name, c.name as category_name,
                   pi.image_url, pi.alt_text,
                   ROUND(AVG(r.rating), 1) as avg_rating,
                   COUNT(r.id) as review_count
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
            LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = TRUE
            WHERE $whereClause
            GROUP BY p.id
            ORDER BY $orderBy
            LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
        ");
        
        $stmt->execute($params);
        
        return [
            'products' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $totalProducts,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalProducts / $perPage)
        ];
    }
    
    /**
     * Get single product with all details
     * @param int $productId Product ID
     * @return array|null Product details
     */
    public function getProductDetails($productId) {
        // Get main product info
        $stmt = $this->pdo->prepare("
            SELECT p.*, b.name as brand_name, c.name as category_name,
                   ROUND(AVG(r.rating), 1) as avg_rating,
                   COUNT(r.id) as review_count
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN reviews r ON p.id = r.product_id AND r.is_approved = TRUE
            WHERE p.id = ? AND p.is_active = TRUE
            GROUP BY p.id
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) return null;
        
        // Get product images
        $stmt = $this->pdo->prepare("
            SELECT * FROM product_images 
            WHERE product_id = ? 
            ORDER BY sort_order, is_primary DESC
        ");
        $stmt->execute([$productId]);
        $product['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get product attributes
        $stmt = $this->pdo->prepare("
            SELECT * FROM product_attributes 
            WHERE product_id = ? 
            ORDER BY attribute_name
        ");
        $stmt->execute([$productId]);
        $product['attributes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent reviews
        $stmt = $this->pdo->prepare("
            SELECT r.*, u.username, up.first_name, up.last_name
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE r.product_id = ? AND r.is_approved = TRUE
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$productId]);
        $product['reviews'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $product;
    }
    
    /**
     * Get related products
     * @param int $productId Current product ID
     * @param int $limit Number of related products
     * @return array Related products
     */
    public function getRelatedProducts($productId, $limit = 4) {
        // Ensure limit is an integer to prevent SQL injection
        $limit = (int)$limit;
        
        $stmt = $this->pdo->prepare("
            SELECT p2.*, b.name as brand_name, c.name as category_name,
                   pi.image_url, pi.alt_text,
                   ROUND(AVG(r.rating), 1) as avg_rating,
                   COUNT(r.id) as review_count
            FROM products p1
            JOIN products p2 ON (p1.category_id = p2.category_id OR p1.brand_id = p2.brand_id)
            LEFT JOIN brands b ON p2.brand_id = b.id
            LEFT JOIN categories c ON p2.category_id = c.id
            LEFT JOIN product_images pi ON p2.id = pi.product_id AND pi.is_primary = TRUE
            LEFT JOIN reviews r ON p2.id = r.product_id AND r.is_approved = TRUE
            WHERE p1.id = ? AND p2.id != ? AND p2.is_active = TRUE AND p2.stock_quantity > 0
            GROUP BY p2.id
            ORDER BY RAND()
            LIMIT " . $limit . "
        ");
        $stmt->execute([$productId, $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all categories for navigation
     * @return array Categories
     */
    public function getCategories() {
        $stmt = $this->pdo->prepare("
            SELECT c.*, COUNT(p.id) as product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.is_active = TRUE
            WHERE c.is_active = TRUE
            GROUP BY c.id
            ORDER BY c.sort_order, c.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all brands for filtering
     * @return array Brands
     */
    public function getBrands() {
        $stmt = $this->pdo->prepare("
            SELECT b.*, COUNT(p.id) as product_count
            FROM brands b
            LEFT JOIN products p ON b.id = p.brand_id AND p.is_active = TRUE
            WHERE b.is_active = TRUE
            GROUP BY b.id
            HAVING product_count > 0
            ORDER BY b.name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search products with autocomplete suggestions
     * @param string $query Search query
     * @param int $limit Number of suggestions
     * @return array Search suggestions
     */
    public function searchSuggestions($query, $limit = 5) {
        // Ensure limit is an integer to prevent SQL injection
        $limit = (int)$limit;
        
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT p.name, p.id
            FROM products p
            WHERE p.name LIKE ? AND p.is_active = TRUE
            ORDER BY p.name
            LIMIT " . $limit . "
        ");
        $stmt->execute(['%' . $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
