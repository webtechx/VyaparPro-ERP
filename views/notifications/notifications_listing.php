<?php
$title = 'Notifications';

$userId = $currentUser['employee_id'];

$sql = "SELECT * FROM notifications WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}
$stmt->close();
?>

<div class="container-fluid">
<div class="row align-items-center mb-3">
        <div class="col-sm-6 text-center text-sm-start">
            <h4 class="mb-0 fw-bold">My Notifications</h4>
            <p class="text-muted mb-0">Stay up to date with the latest alerts and reminders.</p>
        </div>
        <div class="col-sm-6 text-center text-sm-end mt-3 mt-sm-0">
            <?php if(!empty($notifications)): ?>
            <a href="<?= $basePath ?>/delete_notification?id=all" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Are you sure you want to delete all notifications?');">
                <i class="ti ti-trash me-1"></i> Clear All
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="notificationsTable" style="width: 100%;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">Alert Type</th>
                                <th style="width: 27%;">Subject</th>
                                <th style="width: 36%;">Details</th>
                                <th style="width: 12%;">Received On</th>
                                <th class="text-center" style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($notifications)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center opacity-50">
                                        <i class="ti ti-bell-off fs-1 text-muted mb-3"></i>
                                        <h5 class="text-muted">You're all caught up!</h5>
                                        <p class="text-muted mb-0">No new notifications.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): 
                                    $isRead = $notif['is_read'] == 1;
                                    
                                    $iconClass = 'text-primary bg-primary-subtle';
                                    $icon = $notif['icon'] ?: 'ti ti-bell';
                                    
                                    switch($notif['type']) {
                                        case 'success': 
                                            $iconClass = 'text-success bg-success-subtle'; 
                                            $icon = 'ti ti-check';
                                            break;
                                        case 'warning': 
                                            $iconClass = 'text-warning bg-warning-subtle'; 
                                            $icon = 'ti ti-alert-triangle';
                                            break;
                                        case 'error':   
                                            $iconClass = 'text-danger bg-danger-subtle'; 
                                            $icon = 'ti ti-alert-circle';
                                            break;
                                        case 'reminder':
                                            $iconClass = 'text-info bg-info-subtle'; 
                                            $icon = 'ti ti-clock';
                                            break;
                                    }

                                    $statusBadge = $isRead ? '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fs-xs mt-1">Read</span>' : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 fs-xs mt-1">Unread</span>';
                                ?>
                                <tr class="<?= $isRead ? '' : 'bg-light bg-opacity-50' ?>" style="<?= $isRead ? 'opacity: 0.8;' : '' ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title <?= $iconClass ?> rounded-circle fs-5">
                                                    <i class="<?= $icon ?>"></i>
                                                </span>
                                            </div>
                                            <div class="d-flex flex-column align-items-start">
                                                <span class="fw-medium text-capitalize"><?= $notif['type'] ?></span>
                                                <?= $statusBadge ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <h6 class="mb-0 fw-semibold <?= $isRead ? 'text-muted' : 'text-dark' ?>"><?= htmlspecialchars($notif['title']) ?></h6>
                                    </td>
                                    <td>
                                        <p class="mb-0 text-muted fs-sm text-wrap" style="min-width: 250px;">
                                            <?= nl2br(strip_tags($notif['message'])) ?>
                                        </p>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-nowrap"><?= date('d M Y', strtotime($notif['created_at'])) ?></span>
                                            <small class="text-muted text-nowrap"><?= date('h:i A', strtotime($notif['created_at'])) ?></small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <a href="<?= $basePath ?>/read_notification?id=<?= $notif['id'] ?>" class="btn btn-sm <?= $isRead ? 'btn-outline-secondary' : 'btn-primary' ?> rounded-pill px-3">
                                                View
                                            </a>
                                            <a href="<?= $basePath ?>/delete_notification?id=<?= $notif['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Delete" onclick="return confirm('Delete this notification?');">
                                                <i class="ti ti-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<style>
#notificationsTable th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
}
.avatar-sm {
    height: 2.5rem;
    width: 2.5rem;
}
</style>

<script>
$(document).ready(function() {
    $('#notificationsTable').DataTable({
        "order": [], // Let PHP handle the default ordering
        "pageLength": 10,
        "language": {
            "emptyTable": "You have no notifications.",
            "search": "Filter Notifications:",
        },
        "dom": '<"row align-items-center mx-1 mt-3 mb-2"<"col-sm-6"l><"col-sm-6 text-sm-end"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row align-items-center mx-1 my-3"<"col-sm-5"i><"col-sm-7"p>>',
        "columnDefs": [
            { "orderable": false, "targets": [4] } // Disable sorting on Action column
        ]
    });
});
</script>
