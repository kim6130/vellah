document.addEventListener('DOMContentLoaded', function() {
    console.log('ADMIN DASHBOARD: DOM loaded, starting initialization...');
    initDateTime();
    initSidebar();
    initLogout();
    initAdminDashboard();
});

function initDateTime() {
    updateDateTime();
    setInterval(updateDateTime, 1000);
}

function updateDateTime() {
    const now = new Date();
    const dateBox = document.getElementById('dateBox');
    const timeBox = document.getElementById('timeBox');

    if (dateBox) {
        const weekday = now.toLocaleDateString(undefined, { weekday: 'long' });
        const datePart = now.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        dateBox.textContent = `${weekday} · ${datePart}`;
    }

    if (timeBox) {
        let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds(), ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        m = m < 10 ? '0' + m : m;
        s = s < 10 ? '0' + s : s;
        timeBox.textContent = `${h}:${m}:${s} ${ampm}`;
    }
}

function initSidebar() {
    const currentPath = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll(".sidebar nav a");

    navLinks.forEach(link => {
        const linkPath = link.getAttribute("href");
        if (linkPath === currentPath) {
            link.classList.add("active");
        } else {
            link.classList.remove("active");
        }
    });
}

function initLogout() {
    const logoutBtn = document.getElementById("logoutButton");
    const logoutModal = document.getElementById("logoutModal");
    const confirmLogout = document.getElementById("confirmLogout");
    const cancelLogout = document.getElementById("cancelLogout");

    if (logoutBtn && logoutModal) {
        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            logoutModal.style.display = "flex";
        });

        if (confirmLogout) {
            confirmLogout.addEventListener("click", () => {
                window.location.href = "landing.html";
            });
        }

        if (cancelLogout) {
            cancelLogout.addEventListener("click", () => {
                logoutModal.style.display = "none";
            });
        }
    }
}

function initAdminDashboard() {
    setTimeout(() => {
        loadAdminDashboardData();
        generateCalendar();
    }, 1000);
}

function loadAdminDashboardData() {
    console.log('ADMIN DASHBOARD: Starting data load...');
    
    fetch('/AlkanSave/2_Application/controllers/AdminDashboardController.php?action=getAdminDashboardData')
        .then(response => response.text())
        .then(text => {
            console.log('ADMIN DASHBOARD: Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('ADMIN DASHBOARD: JSON parse error', e);
                showError('Invalid server response');
                return;
            }
            
            if (!data.success) {
                console.error('ADMIN DASHBOARD: Server error', data.error);
                showError(data.error || 'Server error');
                return;
            }

            // Update progress rings
            const activePercentage = data.userStats.activePercentage || 0;
            const inactivePercentage = data.userStats.inactivePercentage || 0;
            
            setProgress(activePercentage, 'savingsCircle', 'savingsText');
            setProgress(inactivePercentage, 'goalsCircle', 'goalsText');

            // Update Average Savings per Category
            const avgSavingsElement = document.querySelector('.add-savings-box .total-saved-box p');
            if (avgSavingsElement) {
                const avgAmount = parseFloat(data.avgSavingsPerCategory || 0);
                avgSavingsElement.textContent = '₱' + avgAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            // Update Active Users This Month
            const activeUsersElements = document.querySelectorAll('.total-saved-box p');
            if (activeUsersElements.length >= 2) {
                activeUsersElements[1].textContent = data.activeUsersThisMonth.toString();
            }

            // Update Most Used Categories (FIXED: show up to 5 categories)
            updateTopCategories(data.topCategories || []);

            console.log('ADMIN DASHBOARD: All updates completed successfully!');
        })
        .catch(error => {
            console.error('ADMIN DASHBOARD: Fetch error', error);
            showError('Failed to connect to server');
        });
}

function setProgress(percent, circleId, textId) {
    const circle = document.getElementById(circleId);
    const text = document.getElementById(textId);
    
    if (!circle || !text) {
        console.error('ADMIN DASHBOARD: Progress elements not found', circleId, textId);
        return;
    }
    
    const radius = circle.r.baseVal.value;
    const circumference = 2 * Math.PI * radius;

    circle.style.strokeDasharray = circumference;
    const offset = circumference - (percent / 100) * circumference;
    circle.style.strokeDashoffset = offset;

    text.textContent = Math.round(percent) + '%';
}

function updateTopCategories(categories) {
    const categoriesContainer = document.querySelector('.goals-deadline');
    if (!categoriesContainer) return;
    
    const existingEntries = categoriesContainer.querySelectorAll('.goal-entry');
    existingEntries.forEach(entry => entry.remove());

    if (categories.length === 0) {
        const noCategoriesEntry = document.createElement('div');
        noCategoriesEntry.className = 'goal-entry';
        noCategoriesEntry.innerHTML = '<span>No categories found</span>';
        categoriesContainer.appendChild(noCategoriesEntry);
    } else {
        // FIXED: Show up to 5 most used categories instead of just 1
        const maxCategories = Math.min(5, categories.length);
        for (let i = 0; i < maxCategories; i++) {
            const category = categories[i];
            const categoryEntry = document.createElement('div');
            categoryEntry.className = 'goal-entry';
            categoryEntry.innerHTML = `<span>${category.name} (${category.count} goals)</span>`;
            categoriesContainer.appendChild(categoryEntry);
        }
    }
}

function showError(message) {
    console.error('ADMIN DASHBOARD: Showing error:', message);
    
    const savingsText = document.getElementById('savingsText');
    const goalsText = document.getElementById('goalsText');
    
    if (savingsText) savingsText.textContent = 'Error';
    if (goalsText) goalsText.textContent = 'Error';
    
    const avgSavingsElement = document.querySelector('.add-savings-box .total-saved-box p');
    if (avgSavingsElement) avgSavingsElement.textContent = 'Error loading data';
    
    const activeUsersElements = document.querySelectorAll('.total-saved-box p');
    if (activeUsersElements.length >= 2) {
        activeUsersElements[1].textContent = 'Error';
    }
}

function generateCalendar() {
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();

    const monthNames = [
        "January", "February", "March", "April", "May", "June", 
        "July", "August", "September", "October", "November", "December"
    ];

    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();

    const calendar = document.getElementById('calendarTable');
    const monthYearTitle = document.getElementById('calendarMonthYear');
    
    if (!calendar || !monthYearTitle) return;
    
    calendar.innerHTML = '';
    monthYearTitle.innerText = `${monthNames[currentMonth]} ${currentYear}`;

    const weekdays = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
    let headerRow = "<tr>";
    weekdays.forEach(day => {
        headerRow += `<th>${day}</th>`;
    });
    headerRow += "</tr>";
    calendar.innerHTML += headerRow;

    let day = 1;
    for (let i = 0; i < 6; i++) {
        let row = "<tr>";
        for (let j = 0; j < 7; j++) {
            if ((i === 0 && j < firstDay) || day > totalDays) {
                row += "<td></td>";
            } else {
                const isToday = day === today.getDate();
                row += `<td class="${isToday ? 'today' : ''}">${day}</td>`;
                day++;
            }
        }
        row += "</tr>";
        calendar.innerHTML += row;
        
        if (day > totalDays) break;
    }
}

function redirectToPage() {
    alert('Deactivated accounts feature coming soon!');
}