<?php

require_once __DIR__ . '/../../3_Data/repositories/CategoryRepository.php';

class CategoryService {
    private $categoryRepository;

    public function __construct() {
        $this->categoryRepository = new CategoryRepository();
    }

    public function getMostUsedCategory() {
        try {
            $category = $this->categoryRepository->getMostUsedCategory();
            error_log("CATEGORY SERVICE: Most used category - " . $category);
            return $category;
        } catch (Exception $e) {
            error_log("CategoryService::getMostUsedCategory Error: " . $e->getMessage());
            return 'Error loading data';
        }
    }

    public function getLeastUsedCategory() {
        try {
            $category = $this->categoryRepository->getLeastUsedCategory();
            error_log("CATEGORY SERVICE: Least used category - " . $category);
            return $category;
        } catch (Exception $e) {
            error_log("CategoryService::getLeastUsedCategory Error: " . $e->getMessage());
            return 'Error loading data';
        }
    }

    public function getAllSystemCategories() {
        try {
            $categories = $this->categoryRepository->getAllSystemCategories();
            error_log("CATEGORY SERVICE: All categories count - " . count($categories));
            return $categories;
        } catch (Exception $e) {
            error_log("CategoryService::getAllSystemCategories Error: " . $e->getMessage());
            return [];
        }
    }

    public function addNewCategory($categoryName) {
        try {
            // Validate input
            $categoryName = trim($categoryName);
            if (empty($categoryName)) {
                throw new Exception("Category name cannot be empty");
            }

            if (strlen($categoryName) > 100) {
                throw new Exception("Category name too long (max 100 characters)");
            }

            $categoryId = $this->categoryRepository->addSystemCategory($categoryName);
            error_log("CATEGORY SERVICE: New category added with ID - " . $categoryId);
            return $categoryId;
        } catch (Exception $e) {
            error_log("CategoryService::addNewCategory Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getCategoryUsageStats() {
        try {
            $stats = $this->categoryRepository->getCategoryUsageStats();
            error_log("CATEGORY SERVICE: Category usage stats - " . json_encode($stats));
            return $stats;
        } catch (Exception $e) {
            error_log("CategoryService::getCategoryUsageStats Error: " . $e->getMessage());
            return [];
        }
    }
}
?>