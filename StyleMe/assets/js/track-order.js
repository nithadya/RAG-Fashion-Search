$(document).ready(function() {
    // Check if order number is in URL
    const urlParams = new URLSearchParams(window.location.search);
    const orderNumber = urlParams.get('order');
    
    if (orderNumber) {
        $('#orderNumberInput').val(orderNumber);
        trackOrder(orderNumber);
    }
    
    $('#trackBtn').click(function() {
        const orderNumber = $('#orderNumberInput').val().trim();
        if (orderNumber) {
            trackOrder(orderNumber);
        }
    });
    
    $('#orderNumberInput').keypress(function(e) {
        if (e.which === 13) {
            const orderNumber = $(this).val().trim();
            if (orderNumber) {
                trackOrder(orderNumber);
            }
        }
    });
});

function trackOrder(orderNumber) {
    $.ajax({
        url: `api/orders.php?action=track&order_number=${orderNumber}`,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderTrackingInfo(response.order, response.timeline);
            } else {
                showNoResults();
            }
        },
        error: function() {
            showNoResults();
        }
    });
}

function renderTrackingInfo(order, timeline) {
    $('#orderNumber').text(order.order_number);
    $('#orderStatus').text(order.status);
    
    const $timeline = $('#trackingTimeline');
    $timeline.empty();
    
    const statusSteps = [
        { status: 'Pending', icon: 'fas fa-clock', title: 'Order Placed', description: 'Your order has been placed and is being processed' },
        { status: 'Processing', icon: 'fas fa-cogs', title: 'Processing', description: 'Your order is being prepared for shipment' },
        { status: 'Shipped', icon: 'fas fa-truck', title: 'Shipped', description: 'Your order has been shipped and is on the way' },
        { status: 'Delivered', icon: 'fas fa-check-circle', title: 'Delivered', description: 'Your order has been delivered successfully' }
    ];
    
    statusSteps.forEach((step, index) => {
        const timelineItem = timeline.find(t => t.status === step.status);
        const isCompleted = timelineItem && timelineItem.completed;
        const isCurrent = order.status === step.status;
        
        let itemClass = '';
        if (isCompleted && !isCurrent) itemClass = 'completed';
        if (isCurrent) itemClass = 'current';
        
        $timeline.append(`
            <div class="timeline-item ${itemClass}">
                <div class="timeline-content">
                    <div class="d-flex align-items-center mb-2">
                        <i class="${step.icon} me-2"></i>
                        <h6 class="mb-0">${step.title}</h6>
                    </div>
                    <p class="mb-0 text-muted">${step.description}</p>
                    ${timelineItem && timelineItem.date ? `<small class="text-muted">${new Date(timelineItem.date).toLocaleDateString()}</small>` : ''}
                </div>
            </div>
        `);
    });
    
    $('#trackingResults').show();
    $('#noResults').hide();
}

function showNoResults() {
    $('#trackingResults').hide();
    $('#noResults').show();
}