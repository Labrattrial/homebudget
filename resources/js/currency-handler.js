// Currency Handler
document.addEventListener('DOMContentLoaded', function() {
    // Function to update currency displays
    function updateCurrencyDisplays() {
        const currencySymbol = localStorage.getItem('userCurrencySymbol');
        if (!currencySymbol) return;

        // Update all currency symbols
        document.querySelectorAll('.currency').forEach(el => {
            el.textContent = currencySymbol;
        });

        // Update all amount displays with currency symbol
        document.querySelectorAll('[data-currency-amount]').forEach(el => {
            const amount = el.getAttribute('data-currency-amount');
            if (amount) {
                el.textContent = currencySymbol + parseFloat(amount).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        });

        // Update all amount displays that start with a currency symbol
        document.querySelectorAll('.amount, .stat-value, .category-amount, .transaction-amount, .total-amount, .budget-amount, .remaining-amount, .spent-amount, .category-total, .daily-average, .potential-savings').forEach(el => {
            const text = el.textContent;
            if (text.match(/[₱$€£¥A$]\s*\d+([.,]\d{2})?/)) {
                const amount = parseFloat(text.replace(/[^0-9.-]+/g, ''));
                if (!isNaN(amount)) {
                    el.textContent = currencySymbol + amount.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        });

        // Update all text nodes that contain currency amounts
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        let node;
        while (node = walker.nextNode()) {
            const text = node.textContent;
            if (text.match(/[₱$€£¥A$]\s*\d+([.,]\d{2})?/)) {
                const amount = parseFloat(text.replace(/[^0-9.-]+/g, ''));
                if (!isNaN(amount)) {
                    node.textContent = text.replace(/[₱$€£¥A$]\s*\d+([.,]\d{2})?/, 
                        currencySymbol + amount.toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        })
                    );
                }
            }
        }

        // Update charts if they exist
        if (window.spendingTrendChart) {
            window.spendingTrendChart.options.plugins.tooltip.callbacks.label = function(context) {
                const label = context.label || '';
                const value = context.raw || 0;
                return `${label}: ${currencySymbol}${value.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}`;
            };
            window.spendingTrendChart.update();
        }

        if (window.categoryBreakdownChart) {
            window.categoryBreakdownChart.options.plugins.tooltip.callbacks.label = function(context) {
                const label = context.label || '';
                const value = context.raw || 0;
                return `${label}: ${currencySymbol}${value.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}`;
            };
            window.categoryBreakdownChart.update();
        }

        // Update DataTables if they exist
        if ($.fn.DataTable) {
            $.fn.DataTable.tables({ visible: true, api: true }).columns().every(function() {
                const column = this;
                if (column.header().textContent.includes('Amount') || 
                    column.header().textContent.includes('Spent') || 
                    column.header().textContent.includes('Budget') ||
                    column.header().textContent.includes('Total')) {
                    column.data().each(function(value, index) {
                        if (typeof value === 'string' && (value.startsWith('₱') || value.startsWith('$') || value.startsWith('€') || value.startsWith('£') || value.startsWith('¥') || value.startsWith('A$'))) {
                            const amount = parseFloat(value.replace(/[^0-9.-]+/g, ''));
                            if (!isNaN(amount)) {
                                column.data().set(index, currencySymbol + amount.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }));
                            }
                        }
                    });
                }
            });
        }

        // Update input fields with currency symbol
        document.querySelectorAll('input[type="number"]').forEach(input => {
            const currencyPrefix = input.previousElementSibling;
            if (currencyPrefix && currencyPrefix.classList.contains('currency')) {
                currencyPrefix.textContent = currencySymbol;
            }
        });
    }

    // Initialize currency displays on page load
    updateCurrencyDisplays();

    // Listen for currency updates from other pages
    window.addEventListener('storage', function(e) {
        if (e.key === 'userCurrencySymbol') {
            updateCurrencyDisplays();
        }
    });
}); 