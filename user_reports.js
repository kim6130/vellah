document.addEventListener('DOMContentLoaded', function() {
    const DEBUG = true;
    const API_BASE = 'reports.php';
    let charts = {};

    // Initialize all reports
    initReports();

    async function initReports() {
        try {
            logDebug("Initializing reports...");
            
            // Load all data in parallel
            const [savingsData, goalsData] = await Promise.all([
                fetchReportData('savings_growth'),
                fetchReportData('goal_completion')
            ]);

            // Render charts
            renderSavingsChart(savingsData);
            renderGoalsChart(goalsData);

            // Set up month selector
            setupMonthSelectors();

            // Load initial month data
            const currentMonth = new Date().toLocaleString('default', { month: 'long' });
            const currentMonthElement = document.querySelector(`.month-card[data-month="${currentMonth}"]`);
            if (currentMonthElement) {
                currentMonthElement.classList.add('active-month');
            }
            await loadCompletedGoals(currentMonth);

            logDebug("Reports initialized successfully");
        } catch (error) {
            handleError("Failed to initialize reports", error);
        }
    }

    async function fetchReportData(action, params = {}) {
        const url = new URL(API_BASE, window.location.origin);
        url.searchParams.set('action', action);
        
        // Add user_id from session (this is already handled server-side, but just in case)
        Object.entries(params).forEach(([key, value]) => {
            url.searchParams.set(key, value);
        });

        logDebug(`Fetching ${url}`);

        try {
            const response = await fetch(url);
            
            if (!response.ok) {
                logDebug(`Error response: ${response.status} - ${response.statusText}`);
                throw new Error(`HTTP ${response.status} - ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data && data.error) {
                logDebug(`API error: ${data.error}`);
                throw new Error(data.error);
            }
            
            logDebug(`Received ${action} data:`, data);
            return data;
        } catch (error) {
            logDebug(`Error fetching ${action}: ${error.message}`);
            
            // Return empty data structures
            if (action === 'savings_growth') {
                return { labels: [], datasets: [] };
            } else if (action === 'goal_completion') {
                return {
                    labels: ['Completed', 'Active'],
                    datasets: [{
                        label: 'Goals',
                        data: [0, 0],
                        backgroundColor: ['#fa8fbc', '#ffd1dc'],
                        borderColor: '#fff3f8',
                        borderWidth: 2
                    }],
                    meta: { completion_rate: 0 }
                };
            } else if (action === 'completed_goals') {
                return [];
            }
            
            throw error;
        }
    }

    function renderSavingsChart(data) {
        const ctx = document.getElementById('savingsGrowthChart')?.getContext('2d');
        if (!ctx) {
            logDebug("Savings chart canvas not found");
            return;
        }

        if (charts.savings) {
            charts.savings.destroy();
        }

        // If no data, show empty state
        if (!data.labels || data.labels.length === 0 || !data.datasets || data.datasets.length === 0) {
            renderEmptySavingsChart(ctx);
            return;
        }

        charts.savings = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: data.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `${context.dataset.label}: ₱${context.raw.toLocaleString()}`;
                            }
                        }
                    },
                    legend: {
                        labels: {
                            color: '#24336e',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#24336e',
                            callback: (value) => `₱${value.toLocaleString()}`
                        },
                        grid: {
                            color: 'rgba(36, 51, 110, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#24336e'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function renderEmptySavingsChart(ctx) {
        charts.savings = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['No Data'],
                datasets: [{
                    label: 'No Savings Data',
                    data: [0],
                    backgroundColor: '#f0f0f0',
                    borderColor: '#dddddd',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            display: false
                        },
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        ticks: {
                            color: '#888888'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        const width = ctx.canvas.width;
        const height = ctx.canvas.height;
        ctx.font = 'bold 16px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#888888';
        ctx.fillText('No Savings Data Available', width / 2, height / 2 - 15);
        ctx.font = '14px Arial';
        ctx.fillText('Add goals and transactions to see your progress', width / 2, height / 2 + 15);
    }

    function renderGoalsChart(data) {
        const ctx = document.getElementById('goalCompletionChart')?.getContext('2d');
        if (!ctx) {
            logDebug("Goals chart canvas not found");
            return;
        }

        if (charts.goals) {
            charts.goals.destroy();
        }

        // Check if there's actual data (not just zeros)
        const hasData = data.datasets && 
                       data.datasets[0] && 
                       data.datasets[0].data && 
                       (data.datasets[0].data[0] > 0 || data.datasets[0].data[1] > 0);

        if (!hasData) {
            renderEmptyGoalsChart(ctx);
            return;
        }

        charts.goals = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: data.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#24336e',
                            font: {
                                weight: 'bold'
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.raw / total) * 100);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: data.meta?.completion_rate ? `${data.meta.completion_rate}% Completed` : 'Goal Completion',
                        color: '#24336e',
                        font: {
                            size: 16,
                            weight: 'bold'
                        },
                        position: 'bottom'
                    }
                }
            }
        });

        // Update completion rate display if exists
        const completionElement = document.getElementById('completion-rate');
        if (completionElement && data.meta?.completion_rate) {
            completionElement.textContent = `${data.meta.completion_rate}%`;
        }
    }

    function renderEmptyGoalsChart(ctx) {
        charts.goals = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['No Goals'],
                datasets: [{
                    data: [1],
                    backgroundColor: ['#f0f0f0'],
                    borderColor: '#eeeeee',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });

        const width = ctx.canvas.width;
        const height = ctx.canvas.height;
        ctx.font = 'bold 16px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#888888';
        ctx.fillText('No Goals Yet', width / 2, height / 2 - 10);
        ctx.font = '12px Arial';
        ctx.fillText('Create goals to track completion', width / 2, height / 2 + 15);

        // Update completion rate display to show 0%
        const completionElement = document.getElementById('completion-rate');
        if (completionElement) {
            completionElement.textContent = `0%`;
        }
    }

    function setupMonthSelectors() {
        document.querySelectorAll('.month-card').forEach(card => {
            card.addEventListener('click', async function() {
                const month = this.dataset.month;
                logDebug(`Selected month: ${month}`);
                
                try {
                    // Update active state
                    document.querySelectorAll('.month-card').forEach(c => {
                        c.classList.remove('active-month');
                    });
                    this.classList.add('active-month');
                    
                    // Load data
                    await loadCompletedGoals(month);
                } catch (error) {
                    handleError(`Failed to load ${month} goals`, error);
                }
            });
        });
    }

    async function loadCompletedGoals(monthName) {
        try {
            logDebug(`Loading completed goals for ${monthName}`);
            
            const monthNumber = new Date(`${monthName} 1, 2023`).getMonth() + 1;
            const yearMonth = `${new Date().getFullYear()}-${monthNumber.toString().padStart(2, '0')}`;
            
            const goals = await fetchReportData('completed_goals', { month: yearMonth });
            updateCompletedGoalsTable(goals, monthName);
            
        } catch (error) {
            // Show empty table on error
            updateCompletedGoalsTable([], monthName);
            logDebug(`Error loading goals for ${monthName}: ${error.message}`);
        }
    }

    function updateCompletedGoalsTable(goals, monthName) {
        const tbody = document.querySelector('#completed-goals-body');
        if (!tbody) {
            logDebug("Completed goals table body not found");
            return;
        }

        tbody.innerHTML = '';

        if (!goals || goals.length === 0) {
            tbody.innerHTML = `
                <tr class="no-goals">
                    <td colspan="5">
                        <p style="text-align: center; padding: 20px;">No completed goals for ${monthName}</p>
                    </td>
                </tr>
            `;
            return;
        }

        goals.forEach(goal => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${goal.completion_date}</td>
                <td><span class="category-badge" style="background-color: ${getCategoryColor(goal.CategoryName)}">${goal.CategoryName}</span></td>
                <td>${goal.GoalName}</td>
                <td>${goal.SavedAmount}</td>
                <td>
                    ${goal.TargetAmount}
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${goal.completion_percentage}%"></div>
                    </div>
                    <span class="percentage">${goal.completion_percentage}%</span>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function getCategoryColor(categoryName) {
        const colors = {
            'Travel': '#fa8fbc',
            'Education': '#8fd3f4',
            'Emergency Fund': '#24336e',
            'Gadgets': '#ffd1dc',
            'Default': '#cccccc'
        };
        return colors[categoryName] || colors['Default'];
    }

    function handleError(message, error) {
        console.error(`${message}:`, error);
        
        const errorElement = document.getElementById('error-message') || createErrorElement();
        errorElement.textContent = `${message}. ${error.message}`;
        errorElement.style.display = 'block';
        
        setTimeout(() => {
            errorElement.style.display = 'none';
        }, 5000);
    }

    function createErrorElement() {
        const element = document.createElement('div');
        element.id = 'error-message';
        element.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px;
            background: #ffebee;
            color: #c62828;
            border-radius: 4px;
            box-shadow: 0 3px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
        `;
        document.body.appendChild(element);
        return element;
    }

    function logDebug(...args) {
        if (DEBUG) console.log('[DEBUG]', ...args);
    }
});