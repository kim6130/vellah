document.addEventListener('DOMContentLoaded', function() {
    console.log('CATEGORY MANAGEMENT: DOM loaded, starting initialization...');
    loadCategoryData();
    initAddCategoryModal();
});

function loadCategoryData() {
    console.log('CATEGORY MANAGEMENT: Starting data load...');
    
    fetch('/AlkanSave/2_Application/controllers/CategoryController.php?action=getCategoryData')
        .then(response => response.text())
        .then(text => {
            console.log('CATEGORY MANAGEMENT: Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('CATEGORY MANAGEMENT: JSON parse error', e);
                showError('Invalid server response');
                return;
            }
            
            if (!data.success) {
                console.error('CATEGORY MANAGEMENT: Server error', data.error);
                showError(data.error || 'Server error');
                return;
            }

            // Update Most Used Category
            updateMostUsedCategory(data.mostUsedCategory);
            
            // Update Least Used Category
            updateLeastUsedCategory(data.leastUsedCategory);
            
            // Update All Categories List
            updateCategoriesList(data.allCategories);

            console.log('CATEGORY MANAGEMENT: All updates completed successfully!');
        })
        .catch(error => {
            console.error('CATEGORY MANAGEMENT: Fetch error', error);
            showError('Failed to connect to server');
        });
}

function updateMostUsedCategory(categoryName) {
    const mostUsedElement = document.querySelector('.left-column .category:first-child h3');
    if (mostUsedElement) {
        mostUsedElement.textContent = categoryName;
    }
}

function updateLeastUsedCategory(categoryName) {
    const leastUsedElement = document.querySelector('.left-column .category:last-child h3');
    if (leastUsedElement) {
        leastUsedElement.textContent = categoryName;
    }
}

function updateCategoriesList(categories) {
    const categoriesContainer = document.querySelector('.category-scroll-area');
    if (!categoriesContainer) return;
    
    // Clear existing categories
    categoriesContainer.innerHTML = '';

    if (categories.length === 0) {
        const noCategories = document.createElement('div');
        noCategories.className = 'goals-deadlines';
        noCategories.textContent = 'No categories found';
        categoriesContainer.appendChild(noCategories);
    } else {
        categories.forEach(category => {
            const categoryElement = document.createElement('div');
            categoryElement.className = 'goals-deadlines';
            categoryElement.textContent = category.CategoryName;
            categoriesContainer.appendChild(categoryElement);
        });
    }
}

function initAddCategoryModal() {
    const addCBtn = document.getElementById("addCBtn");
    const addCModal = document.getElementById("addCmodal");
    const confirmaddC = document.getElementById("confirmAddC");
    const canceladdC = document.getElementById("cancelAddC");
    const categoryNameInput = document.getElementById("categoryName");

    if (addCBtn && addCModal) {
        addCBtn.addEventListener("click", (e) => {
            e.preventDefault();
            addCModal.style.display = "flex";
            categoryNameInput.value = ''; // Clear input
            categoryNameInput.focus();
        });

        if (confirmaddC) {
            confirmaddC.addEventListener("click", (e) => {
                e.preventDefault();
                addNewCategory();
            });
        }

        if (canceladdC) {
            canceladdC.addEventListener("click", (e) => {
                e.preventDefault();
                addCModal.style.display = "none";
            });
        }

        // Allow Enter key to submit
        if (categoryNameInput) {
            categoryNameInput.addEventListener("keypress", (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addNewCategory();
                }
            });
        }
    }
}

function addNewCategory() {
    const categoryNameInput = document.getElementById("categoryName");
    const categoryName = categoryNameInput.value.trim();

    if (!categoryName) {
        alert('Please enter a category name');
        return;
    }

    if (categoryName.length > 100) {
        alert('Category name is too long (max 100 characters)');
        return;
    }

    console.log('CATEGORY MANAGEMENT: Adding new category:', categoryName);

    // Disable button to prevent multiple submissions
    const confirmBtn = document.getElementById("confirmAddC");
    const originalText = confirmBtn.textContent;
    confirmBtn.textContent = 'Adding...';
    confirmBtn.disabled = true;

    fetch('/AlkanSave/2_Application/controllers/CategoryController.php?action=addCategory', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            categoryName: categoryName
        })
    })
    .then(response => response.text())
    .then(text => {
        console.log('CATEGORY MANAGEMENT: Add category response:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('CATEGORY MANAGEMENT: JSON parse error', e);
            alert('Invalid server response');
            return;
        }
        
        if (data.success) {
            alert('Category added successfully!');
            document.getElementById("addCmodal").style.display = "none";
            loadCategoryData(); // Reload the page data
        } else {
            alert('Error: ' + (data.error || 'Failed to add category'));
        }
    })
    .catch(error => {
        console.error('CATEGORY MANAGEMENT: Add category error', error);
        alert('Failed to connect to server');
    })
    .finally(() => {
        // Re-enable button
        confirmBtn.textContent = originalText;
        confirmBtn.disabled = false;
    });
}

function showError(message) {
    console.error('CATEGORY MANAGEMENT: Showing error:', message);
    
    // Update display elements to show error
    const mostUsedElement = document.querySelector('.left-column .category:first-child h3');
    const leastUsedElement = document.querySelector('.left-column .category:last-child h3');
    
    if (mostUsedElement) mostUsedElement.textContent = 'Error';
    if (leastUsedElement) leastUsedElement.textContent = 'Error';
    
    const categoriesContainer = document.querySelector('.category-scroll-area');
    if (categoriesContainer) {
        categoriesContainer.innerHTML = '<div class="goals-deadlines">Error loading categories</div>';
    }
}